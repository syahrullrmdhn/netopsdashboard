<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Customer;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Log;

class TicketController extends Controller
{
    // ─────────────────────────────────────────────────────
    // 1) LISTING & CRUD
    // ─────────────────────────────────────────────────────

    /**
     * Display a paginated list of tickets, with search & status filters.
     */
public function index(Request $request)
{
    $search = $request->input('search');
    $status = $request->input('status');

    // Get db name for dynamic join
    $customerDb = config('database.connections.customerdb.database');

    $query = \DB::table('tickets as t')
        ->join("$customerDb.customers as c", 't.customer_id', '=', 'c.id')
        ->leftJoin("$customerDb.suppliers as s", 'c.kdsupplier', '=', 's.kdsupplier')
        ->leftJoin('users as u', 't.user_id', '=', 'u.id')
        ->select(
            't.*',
            'c.customer as customer_name',
            'c.cid_abh as customer_cid_abh',
            'c.cid_supp as customer_cid_supp',
            's.nama_supplier as supplier_name',
            'u.name as user_name'
        );

    // Filter search
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('t.ticket_number', 'like', "%{$search}%")
                ->orWhere('t.supplier_ticket_number', 'like', "%{$search}%")
                ->orWhere('t.issue_type', 'like', "%{$search}%")
                ->orWhere('c.customer', 'like', "%{$search}%");
        });
    }

    // Status filter
    if ($status === 'open') {
        $query->whereNull('t.end_time');
    } elseif ($status === 'closed') {
        $query->whereNotNull('t.end_time');
    }

    $tickets = $query
        ->orderByDesc('t.id')
        ->paginate(15)
        ->appends($request->only('search', 'status'));

    return view('tickets.index', compact('tickets', 'search', 'status'));
}
    /**
     * Show form to create a new ticket.
     */
    public function create()
    {
        $customers = Customer::orderBy('customer')
                             ->get(['id','customer','cid_abh']);
        return view('tickets.create', compact('customers'));
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
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

        $data += [
            'user_id'      => auth()->id(),
            'alert'        => $request->has('alert'),
            'sla_duration' => 0,
        ];

        $ticket = Ticket::create($data);

        return redirect()->route('tickets.show', $ticket)
                         ->with('success','Ticket created.');
    }

    /**
     * Show a single ticket detail.
     */
public function show($id)
{
    // Join ke customer & supplier seperti index (pakai stdClass)
    $customerDb = config('database.connections.customerdb.database');
    $ticket = \DB::table('tickets as t')
        ->join("$customerDb.customers as c", 't.customer_id', '=', 'c.id')
        ->leftJoin("$customerDb.suppliers as s", 'c.kdsupplier', '=', 's.kdsupplier')
        ->leftJoin('users as u', 't.user_id', '=', 'u.id')
        ->select(
            't.*',
            'c.customer as customer_name',
            'c.cid_abh as customer_cid_abh',
            'c.cid_supp as customer_cid_supp',
            's.nama_supplier as supplier_name',
            'u.name as user_name'
        )
        ->where('t.id', $id)
        ->first();

    // Ambil updates ticket
    $updates = \DB::table('ticket_updates as tu')
        ->leftJoin('users as u', 'tu.user_id', '=', 'u.id')
        ->where('tu.ticket_id', $id)
        ->orderBy('tu.created_at')
        ->select('tu.*', 'u.name as user_name')
        ->get();

    // Kirim ke view
    return view('tickets.show', compact('ticket', 'updates'));
}



    /**
     * Update an existing ticket.
     */
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

    /**
     * Mark a ticket as closed (sets end_time = now).
     */
    public function close(Ticket $ticket)
    {
        $ticket->update(['end_time' => now()]);
        return back()->with('success','Ticket closed.');
    }

    // ─────────────────────────────────────────────────────
    // 2) RFO Preview + PDF
    // ─────────────────────────────────────────────────────

    /**
     * Show the HTML RFO in‐browser.
     */
public function rfo($id)
{
    $customerDb = config('database.connections.customerdb.database');
    $raw = \DB::table('tickets as t')
        ->join("$customerDb.customers as c", 't.customer_id', '=', 'c.id')
        ->leftJoin("$customerDb.suppliers as s", 'c.kdsupplier', '=', 's.kdsupplier')
        ->leftJoin('users as u', 't.user_id', '=', 'u.id')
        ->select(
            't.*',
            'c.customer as customer',
            'c.cid_abh as cid_abh',
            'c.cid_supp as cid_supp',
            's.nama_supplier as supplier',
            'u.name as user'
        )
        ->where('t.id', $id)
        ->first();

    if (!$raw) abort(404);

    $ticket = (object) $raw;

    $updates = \DB::table('ticket_updates')
        ->leftJoin('users', 'ticket_updates.user_id', '=', 'users.id')
        ->where('ticket_id', $id)
        ->orderBy('ticket_updates.created_at', 'desc')
        ->select('ticket_updates.*', 'users.name as user')
        ->get();

    return view('tickets.rfo-pdf', compact('ticket', 'updates'));
}

    /**
     * Download the RFO as a PDF.
     */
public function rfoPdf(Request $request, $id)
{
    $customerDb = config('database.connections.customerdb.database');
    $raw = \DB::table('tickets as t')
        ->join("$customerDb.customers as c", 't.customer_id', '=', 'c.id')
        ->leftJoin("$customerDb.suppliers as s", 'c.kdsupplier', '=', 's.kdsupplier')
        ->leftJoin('users as u', 't.user_id', '=', 'u.id')
        ->select(
            't.*',
            'c.customer as customer',
            'c.cid_abh as cid_abh',
            'c.cid_supp as cid_supp',
            's.nama_supplier as supplier',
            'u.name as user'
        )
        ->where('t.id', $id)
        ->first();

    if (!$raw) abort(404);

    $ticket = (object) $raw;

    // override (jika ada perubahan dari modal RFO)
    $ticket->problem_detail    = $request->input('problem_detail',    $ticket->problem_detail);
    $ticket->action_taken      = $request->input('action_taken',      $ticket->action_taken);
    $ticket->preventive_action = $request->input('preventive_action', $ticket->preventive_action);

    $updates = \DB::table('ticket_updates')
        ->leftJoin('users', 'ticket_updates.user_id', '=', 'users.id')
        ->where('ticket_id', $id)
        ->orderBy('ticket_updates.created_at', 'desc')
        ->select('ticket_updates.*', 'users.name as user')
        ->get();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('tickets.rfo-pdf', compact('ticket', 'updates'))
        ->setPaper('a4', 'portrait');

    return $pdf->download("RFO-{$ticket->ticket_number}.pdf");
}


    // ─────────────────────────────────────────────────────
    // 3) IMPORT / EXPORT Excel via Spout
    // ─────────────────────────────────────────────────────

    /**
     * Import tickets from an uploaded Excel file.
     */
    public function import(Request $request)
    {
        $request->validate(['file'=>'required|file|mimes:xlsx,xls']);
        $ext    = strtolower($request->file('file')->getClientOriginalExtension());
        $reader = $ext==='xlsx'
            ? ReaderEntityFactory::createXLSXReader()
            : ReaderEntityFactory::createXLSReader();

        $reader->open($request->file('file')->getRealPath());
        $customers = Customer::select('id','customer','cid_abh')->get();

        $saved = 0; $skipped = 0; $reasons = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $header = null; $dateKey = null; $rowNo = 0;
            foreach ($sheet->getRowIterator() as $row) {
                $rowNo++;
                $cells = $row->toArray();

                if ($rowNo === 1) {
                    $header = array_map(fn($h) => Str::slug($h,'_'), $cells);
                    foreach (['date_start_time','open_date','start_date'] as $cand) {
                        if (in_array($cand, $header, true)) {
                            $dateKey = $cand;
                            break;
                        }
                    }
                    if (!$dateKey) {
                        $reasons[] = "Sheet “{$sheet->getName()}”: missing start-date column.";
                        break;
                    }
                    continue;
                }
                if (!$header) {
                    continue;
                }

                // map row to associative array
                $data = [];
                foreach ($header as $i => $col) {
                    $val = $cells[$i] ?? null;
                    $data[$col] = $val instanceof \DateTimeInterface
                                 ? $val->format('Y-m-d H:i:s')
                                 : trim((string)($val ?? ''));
                }

                // validate start_date
                if ($data[$dateKey] === '') {
                    $this->logSkip($rowNo, 'missing start-date', $reasons, $skipped);
                    continue;
                }
                try {
                    $start = Carbon::parse($data[$dateKey]);
                } catch (\Exception $e) {
                    $this->logSkip($rowNo, 'invalid start-date format', $reasons, $skipped);
                    continue;
                }

                // parse optional end
                $end = null;
                if (!empty($data['date_end_time'] ?? '')) {
                    try {
                        $end = Carbon::parse($data['date_end_time']);
                    } catch (\Exception $e) {
                        // ignore parse errors
                    }
                }

                // match customer
                $cust = $this->matchCustomer($data, $customers);
                if (!$cust) {
                    $this->logSkip($rowNo, 'customer not found', $reasons, $skipped);
                    continue;
                }

                // create ticket
                $ticket = Ticket::create([
                    'customer_id'            => $cust->id,
                    'open_date'              => $start->toDateString(),
                    'start_time'             => $start,
                    'end_time'               => $end,
                    'issue_type'             => $data['type_of_issue']           ?? null,
                    'supplier_ticket_number' => $data['supplier_ticket_number'] ?? null,
                    'problem_detail'         => $data['root_cause']             ?? null,
                    'action_taken'           => $data['action_taken']           ?? null,
                    'preventive_action'      => $data['preventive_action']      ?? null,
                    'alert'                  => in_array(strtolower($data['status_rfo_send'] ?? ''), ['yes','true','1']),
                ]);
                $saved++;

                if (!empty($data['chronology'])) {
                    $ticket->updates()->create([
                        'detail'  => $data['chronology'],
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        }

        $reader->close();

        $msgSuccess = "{$saved} ticket(s) imported successfully.";
        $msgWarn    = $skipped
            ? "{$skipped} row(s) skipped: ".implode(' | ', array_slice($reasons,0,5))
            : null;

        return redirect()->route('tickets.index')
                         ->with('success', $msgSuccess)
                         ->with('warning', $msgWarn);
    }

    /**
     * Download an Excel template for import.
     */
    public function exportTemplate()
    {
        $headers = [
            'No','Customer','Suplier','CID ABH','CID Supplier',
            'Type of Issue','ABH Ticket Number','Supplier Ticket Number',
            'Status','Date/Start Time','Date/End Time','Duration','Timezone',
            'On Duty','Root Cause','Action Taken','Chronology',
            'Cordination To Customer','Cordination To Supplier','Status RFO Send',
        ];

        $writer = WriterEntityFactory::createXLSXWriter();
        $tmp    = storage_path('app/tmp_template.xlsx');
        $writer->openToFile($tmp);
        $writer->addRow(WriterEntityFactory::createRowFromArray($headers));
        $writer->close();

        return response()
            ->download($tmp, 'ticket-template.xlsx')
            ->deleteFileAfterSend(true);
    }

    /**
     * Export all tickets to Excel.
     */
    public function exportTickets()
    {
        $writer = WriterEntityFactory::createXLSXWriter();
        $fname  = 'tickets-'.now()->format('Ymd_His').'.xlsx';
        $tmp    = storage_path("app/tmp_{$fname}");
        $writer->openToFile($tmp);

        $headers = [
            'No','Customer','Suplier','CID ABH','CID Supplier',
            'Type of Issue','ABH Ticket Number','Supplier Ticket Number',
            'Status','Date/Start Time','Date/End Time','Duration','Timezone',
            'On Duty','Root Cause','Action Taken','Chronology',
            'Cordination To Customer','Cordination To Supplier','Status RFO Send',
        ];
        $writer->addRow(WriterEntityFactory::createRowFromArray($headers));

        $i = 0;
        // cursor() untuk memory-efficient streaming
        $query = Ticket::with('customer.supplier','updates','user')
                       ->orderBy('id')
                       ->cursor();

        foreach ($query as $t) {
            $i++;
            $row = [
                $i,
                optional($t->customer)->customer,
                optional($t->customer->supplier)->nama_supplier,
                optional($t->customer)->cid_abh,
                optional($t->customer)->cid_supp,
                $t->issue_type,
                $t->ticket_number,
                $t->supplier_ticket_number,
                $t->end_time ? 'Closed' : 'Open',
                optional($t->start_time)->format('Y-m-d H:i:s'),
                optional($t->end_time)->format('Y-m-d H:i:s'),
                ($t->start_time && $t->end_time)
                    ? $t->end_time->diffInMinutes($t->start_time).'m' : '',
                'Asia/Jakarta',
                optional($t->user)->name,
                $t->problem_detail,
                $t->action_taken,
                $t->updates->pluck('detail')->implode(' | '),
                '', '', $t->alert ? 'YES':'NO',
            ];
            $writer->addRow(WriterEntityFactory::createRowFromArray($row));
        }

        $writer->close();

        return response()
            ->download($tmp, $fname)
            ->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────
    // 4) HELPERS
    // ─────────────────────────────────────────────────────

    /**
     * Log and count skipped rows during import.
     */
    protected function logSkip(int $row, string $why, array &$reasons, int &$skip)
    {
        $skip++;
        Log::warning("ImportTickets: row {$row} skipped – {$why}");
        if (count($reasons) < 5) {
            $reasons[] = "Row {$row}: {$why}";
        }
    }

    /**
     * Attempt to match imported row to an existing customer.
     */
    protected function matchCustomer(array $row, $customers)
    {
        $cid  = $row['cid_abh']  ?? '';
        $name = $row['customer'] ?? '';

        // 1) exact CID match
        if ($cid && ($c = $customers->firstWhere('cid_abh', $cid))) {
            return $c;
        }

        // 2) fuzzy by name ≥80%
        $bestPct = 0; $best = null;
        foreach ($customers as $c) {
            similar_text(mb_strtolower($name), mb_strtolower($c->customer), $pct);
            if ($pct > $bestPct) {
                $bestPct = $pct; $best = $c;
            }
        }
        if ($bestPct >= 80) {
            Log::info("ImportTickets: fuzzy-name '{$name}'→'{$best->customer}' ({$bestPct}%)");
            return $best;
        }

        // 3) fuzzy by CID (Levenshtein ≤2)
        $bestDist = PHP_INT_MAX; $bestCid = null;
        foreach ($customers as $c) {
            $d = levenshtein($cid, $c->cid_abh);
            if ($d < $bestDist) {
                $bestDist = $d; $bestCid = $c;
            }
        }
        if ($bestDist <= 2) {
            Log::info("ImportTickets: fuzzy-cid '{$cid}'→'{$bestCid->cid_abh}' (lev={$bestDist})");
            return $bestCid;
        }

        return null;
    }
}
