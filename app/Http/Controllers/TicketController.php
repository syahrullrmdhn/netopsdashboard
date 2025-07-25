<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Ticket, Customer, TicketUpdate};
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $status   = $request->input('status');
        $dateFrom = $request->input('date_from') ?: now()->startOfMonth()->toDateString();
        $dateTo   = $request->input('date_to')   ?: now()->endOfMonth()->toDateString();

        $customerIds = [];
        if ($search) {
            $customerIds = Customer::query()
                ->where('customer', 'like', "%{$search}%")
                ->orWhere('cid_abh',  'like', "%{$search}%")
                ->pluck('id')
                ->toArray();
        }

        $tickets = Ticket::with([
                'customer.supplier',
                'customer.group',
                'customer.serviceType',
                'user',
                'updates'
            ])
            ->when($search, function($q) use ($search, $customerIds) {
                $q->where(function($q2) use ($search, $customerIds) {
                    $q2->where('ticket_number',            'like', "%{$search}%")
                       ->orWhere('supplier_ticket_number', 'like', "%{$search}%")
                       ->orWhere('issue_type',             'like', "%{$search}%");
                    if (! empty($customerIds)) {
                        $q2->orWhereIn('customer_id', $customerIds);
                    }
                });
            })
            ->when($status === 'open',   fn($q) => $q->whereNull('end_time'))
            ->when($status === 'closed', fn($q) => $q->whereNotNull('end_time'))
            ->whereNotNull('customer_id')
            ->whereBetween('open_date', [$dateFrom, $dateTo])
            ->orderByDesc('id')
            ->paginate(15)
            ->appends($request->only('search','status','date_from','date_to'));

        $periodStart = Carbon::parse($dateFrom)->startOfDay();
        $periodEnd   = Carbon::parse($dateTo)->endOfDay();
        $periodSec   = $periodEnd->diffInSeconds($periodStart);

        $visibleCustomerIds = $tickets->pluck('customer_id')->unique()->filter()->all();

        $linkDownTickets = Ticket::whereIn('customer_id', $visibleCustomerIds)
            ->whereRaw("LOWER(issue_type) LIKE ?", ['%link down%'])
            ->whereDate('start_time', '>=', $dateFrom)
            ->whereDate('start_time', '<=', $dateTo)
            ->get();

        $slaMap = [];
        foreach ($visibleCustomerIds as $cid) {
            $tgt = 99.5;
            foreach ($tickets as $t) {
                if ($t->customer_id == $cid && $t->customer && $t->customer->sla) {
                    $tgt = floatval($t->customer->sla);
                    break;
                }
            }

            $downtime = $linkDownTickets
                ->where('customer_id', $cid)
                ->sum(fn($t) => $t->start_time
                    ? Carbon::parse($t->end_time ?: now())
                            ->diffInSeconds(Carbon::parse($t->start_time))
                    : 0
                );

            $real = max($tgt - round(($downtime / $periodSec) * 100, 2), 0);
            $statusPct = $tgt > 0
                ? round(($real / $tgt) * 100, 2)
                : 0;

            $slaMap[$cid] = [
                'target'    => $tgt,
                'real'      => $real,
                'downtime'  => $downtime,
                'statusPct' => $statusPct,
            ];
        }

        foreach ($tickets as $t) {
            if ($c = $t->customer) {
                $cid = $t->customer_id;
                if (isset($slaMap[$cid])) {
                    $m = $slaMap[$cid];
                    $c->sla_target    = $m['target'];
                    $c->sla_realtime  = $m['real'];
                    $c->sla_downtime  = $m['downtime'];
                    $c->sla_statusPct = $m['statusPct'];
                }
            }
        }

        return view('tickets.index', compact(
            'tickets','search','status','dateFrom','dateTo','periodSec'
        ));
    }

    public function create()
    {
        $customers = Customer::with(['group','serviceType'])
            ->orderBy('customer')
            ->get(['id','customer','cid_abh','customer_group_id','service_type_id']);

        return view('tickets.create', compact('customers'));
    }

    public function store(Request $request)
    {
        // 1. Validasi input
        $data = $request->validate([
            'open_date'              => 'required|date',
            'customer_id'            => 'required|exists:customerdb.customers,id',
            'start_time'             => 'nullable|date',
            'end_time'               => 'nullable|date|after_or_equal:start_time',
            'issue_type'             => 'required|string|max:255',
            'supplier_ticket_number' => 'nullable|string|max:255',
            'problem_detail'         => 'nullable|string',
            'action_taken'           => 'nullable|string',
            'preventive_action'      => 'nullable|string',
            'alert'                  => 'sometimes|boolean',
        ]);

        // 2. Tambah field otomatis
        $data += [
            'user_id'      => auth()->id(),
            'alert'        => $request->has('alert'),
            'sla_duration' => 0,
        ];

        // 3. Buat ticket
        $ticket = Ticket::create($data);

        // 4. Kirim notifikasi ke WhatsApp Group via Bot
        try {
            Http::post(config('services.wa_bot.url') . '/api/notify-ticket-open', [
                'group_id'      => config('services.wa_bot.group_id'),
                'ticket_number' => $ticket->ticket_number,
                'customer'      => $ticket->customer->customer ?? '—',
                'issue'         => $ticket->issue_type,
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to notify ticket open: " . $e->getMessage());
        }

        // 5. Redirect dengan pesan sukses
        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket created and notification sent.');
    }

    public function show($id)
    {
        $ticket = Ticket::with([
            'customer.supplier',
            'customer.group',
            'customer.serviceType',
            'user',
            'updates.user'
        ])->findOrFail($id);

        return view('tickets.show', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'open_date'              => 'required|date',
            'start_time'             => 'nullable|date',
            'end_time'               => 'nullable|date|after_or_equal:start_time',
            'issue_type'             => 'required|string|max:255',
            'supplier_ticket_number' => 'nullable|string|max:255',
            'problem_detail'         => 'nullable|string',
            'action_taken'           => 'nullable|string',
            'preventive_action'      => 'nullable|string',
            'alert'                  => 'sometimes|boolean',
        ]);

        $ticket->update($data + ['alert' => $request->has('alert')]);

        return back()->with('success','Ticket updated.');
    }

 public function close(Ticket $ticket)
    {
        // 1. Update end_time ke sekarang
        $ticket->update(['end_time' => now()]);

        // 2. Kirim notifikasi close ke WhatsApp Group via Bot
        try {
            Http::post(config('services.wa_bot.url') . '/api/notify-ticket-close', [
                'group_id'      => config('services.wa_bot.group_id'),
                'ticket_number' => $ticket->ticket_number,
                'customer'      => $ticket->customer->customer ?? '—',
                'issue'         => $ticket->issue_type,
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to notify ticket close: " . $e->getMessage());
        }

        // 3. Kembali ke halaman sebelumnya dengan pesan sukses
        return back()->with('success', 'Ticket closed and notification sent.');
    }

    public function rfo($id)
    {
        $ticket = Ticket::with([
            'customer.supplier',
            'customer.group',
            'customer.serviceType',
            'user',
            'updates.user'
        ])->findOrFail($id);

        return view('tickets.rfo-pdf', compact('ticket'));
    }

    public function rfoPdf(Request $request, $id)
    {
        $ticket = Ticket::with([
            'customer.supplier',
            'customer.group',
            'customer.serviceType',
            'user',
            'updates.user'
        ])->findOrFail($id);

        $ticket->problem_detail    = $request->input('problem_detail',    $ticket->problem_detail);
        $ticket->action_taken      = $request->input('action_taken',      $ticket->action_taken);
        $ticket->preventive_action = $request->input('preventive_action', $ticket->preventive_action);

        $pdf = Pdf::loadView('tickets.rfo-pdf', compact('ticket'))
                  ->setPaper('a4','portrait');

        return $pdf->download("RFO-{$ticket->ticket_number}.pdf");
    }

    public function updateChronology(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'updates.*.detail'    => 'required|string',
            'updates.*.timestamp' => 'required|date',
        ]);

        foreach ($data['updates'] as $id => $upd) {
            $entry = TicketUpdate::find($id);
            if ($entry && $entry->ticket_id === $ticket->id) {
                $entry->detail      = $upd['detail'];
                $entry->timestamps  = false;
                $dt                 = Carbon::parse($upd['timestamp']);
                $entry->created_at  = $dt;
                $entry->updated_at  = $dt;
                $entry->save();
                $entry->timestamps  = true;
            }
        }

        return back()->with('success','Chronology updated.');
    }

    public function apiOpenTickets(): JsonResponse
    {
        $tickets = Ticket::whereNull('end_time')
            ->with(['customer','updates'])
            ->orderBy('open_date')
            ->get()
            ->map(fn($t) => [
                'ticket_number' => $t->ticket_number,
                'customer'      => $t->customer->customer ?? '—',
                'issue'         => $t->issue_type,
                'last_update'   => optional($t->updates->first()?->created_at)->format('d/m H:i') ?? 'No updates',
            ]);

        return response()->json($tickets);
    }

    public function apiShowByNumber(string $ticket_number): JsonResponse
    {
        $ticket = Ticket::with(['customer','updates.user'])
                    ->where('ticket_number', $ticket_number)
                    ->firstOrFail();

        return response()->json([
            'ticket_number'  => $ticket->ticket_number,
            'customer'       => $ticket->customer->customer ?? '—',
            'status'         => $ticket->end_time ? 'Closed' : 'Open',
            'priority'       => $ticket->alert ? 'High' : 'Normal',
            'created_at'     => $ticket->open_date?->toDateTimeString() ?? '',
            'updated_at'     => $ticket->updated_at?->toDateTimeString() ?? '',
            'problem_detail' => $ticket->problem_detail,
            'chronology'     => $ticket->updates->map(fn($u) => [
                'user'      => $u->user->name ?? 'System',
                'detail'    => $u->detail,
                'timestamp' => $u->created_at?->toDateTimeString() ?? '',
            ]),
        ]);
    }
        public function downloadRfoDocx($id)
    {
        // Load the ticket with relations
        $ticket = Ticket::with([
            'customer.supplier',
            'customer.group',
            'customer.serviceType',
            'user',
            'updates'
        ])->findOrFail($id);

        // Path to your Word template (kop only + placeholders)
        $templatePath = storage_path('app/templates/Template RFO.docx');
        $template     = new TemplateProcessor($templatePath);

        // Set simple text placeholders
        $template->setValue('ticket_number',    $ticket->ticket_number);
        $template->setValue('open_date',        $ticket->open_date->format('d/m/Y H:i'));
        $template->setValue('customer',         $ticket->customer->customer ?? '-');
        $template->setValue('issue_type',       $ticket->issue_type ?? '-');
        $template->setValue('service',          $ticket->customer->serviceType->service_name ?? '-');
        $template->setValue('start_time',       $ticket->start_time?->format('d/m/Y H:i') ?: '-');
        $template->setValue('end_time',         $ticket->end_time?->format('d/m/Y H:i') ?: '-');
        $template->setValue('status',           strtoupper($ticket->status ?? ''));
        $template->setValue('severity',         strtoupper($ticket->severity ?? ''));
        $template->setValue('system',           $ticket->system ?? 'OWS');
        $template->setValue('report_datetime',  now()->format('Y-m-d H:i:s').' UTC');
        $template->setValue('description',      strip_tags($ticket->problem_detail ?? '-'));
        $template->setValue('resolution_actions', strip_tags($ticket->action_taken ?? '-'));
        $template->setValue('preventive_measures', strip_tags($ticket->preventive_action ?? '-'));
        $template->setValue('approved_by',      'Supyar Daulay');
        $template->setValue('date_signed',      now()->format('d F Y'));

        // Generate chronology table rows (requires placeholders in template: update_date & update_remark)
        $updates = $ticket->updates;
        $template->cloneRow('update_date', count($updates));
        foreach ($updates as $i => $u) {
            $idx = $i + 1;
            $template->setValue("update_date#{$idx}",   $u->created_at->format('Y-m-d H:i'));
            $template->setValue("update_remark#{$idx}", htmlspecialchars($u->detail ?: 'No Detail Provided'));
        }

        // Save to temporary file and download
        $fileName = "RFO_{$ticket->ticket_number}.docx";
        $tempPath = storage_path("app/public/{$fileName}");
        $template->saveAs($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }
}
