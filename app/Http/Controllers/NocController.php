<?php

namespace App\Http\Controllers;

use App\Models\NocShiftAssignment;
use App\Models\HandoverLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class NocController extends Controller
{
    public function manageShifts()
    {
        $users       = User::orderBy('name')->get();
        $today       = now()->toDateString();
        $assignments = NocShiftAssignment::where('date', $today)
            ->get()->keyBy('shift');

        return view('noc.manage-shifts', compact('users', 'assignments', 'today'));
    }

    public function updateShifts(Request $request)
    {
        $today = now()->toDateString();
        foreach (['pagi', 'siang', 'malam'] as $shift) {
            $uid = $request->input("assignment.$shift");
            if (! $uid) continue;
            NocShiftAssignment::updateOrCreate(
                ['date' => $today, 'shift' => $shift],
                ['user_id' => $uid]
            );
        }
        return back()->with('success', 'Assignments updated.');
    }

    public function handover()
    {
        $today = now()->toDateString();
        $logs  = HandoverLog::where('date', $today)
            ->orderBy('created_at')
            ->get();

        $order = ['pagi', 'siang', 'malam'];
        if ($logs->isEmpty()) {
            $cur = 'pagi';
        } else {
            $last = $logs->last()->shift;
            $idx  = array_search($last, $order);
            $cur  = $order[($idx + 1) % 3];
        }

        $nidx = array_search($cur, $order);
        $next = $order[($nidx + 1) % 3];

        $asgs = NocShiftAssignment::where('date', $today)
            ->whereIn('shift', [$cur, $next])
            ->with('user')
            ->get()->keyBy('shift');

        $curUser  = $asgs[$cur]->user ?? null;
        $nextUser = $asgs[$next]->user ?? null;

        $tickets = Ticket::whereNull('end_time')
            ->with(['customer.supplier', 'updates'])
            ->orderBy('open_date')
            ->get();

        $markdown = $tickets->map(function ($t) {
            $u = $t->updates->first();
            $last = $u
                ? "Update terakhir: {$u->detail} ({$u->created_at->format('d/m H:i')})"
                : 'Belum ada update.';
            return "* **#{$t->ticket_number}** - {$t->customer->customer} ({$t->issue_type}). {$last}";
        })->implode("\n");

        return view('noc.handover', compact(
            'cur', 'next', 'curUser', 'nextUser', 'tickets', 'markdown'
        ));
    }

    public function storeHandover(Request $r)
    {
        // 1. Validasi input
        $data = $r->validate([
            'shift'      => 'required|string',
            'to_user_id' => 'nullable|exists:users,id',
            'issues'     => 'required|string',
            'notes'      => 'nullable|string',
        ]);

        // 2. Buat record handover
        $handover = HandoverLog::create([
            'date'         => now()->toDateString(),
            'shift'        => $data['shift'],
            'from_user_id' => Auth::id(),
            'to_user_id'   => $data['to_user_id'],
            'issues'       => $data['issues'],
            'notes'        => $data['notes'],
        ]);

        // 3. Kirim ke WhatsApp‑bot
        try {
            Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(env('WA_BOT_URL').'/api/notify-handover', [
                'message' => self::formatHandoverMessage($handover),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send handover to WhatsApp: '.$e->getMessage());
        }

        // 4. Redirect balik dengan success message
        return back()->with('success', 'Handover logged successfully.');
    }

    public function history()
    {
        $today = now()->toDateString();
        $logs  = HandoverLog::with(['fromUser', 'toUser'])
            ->where('date', $today)
            ->orderBy('created_at')
            ->paginate(15);

        return view('noc.history', compact('logs', 'today'));
    }

    public function apiOnDuty(): JsonResponse
    {
        $today = Carbon::now()->toDateString();
        $order = ['pagi', 'siang', 'malam'];

        $logs = HandoverLog::where('date', $today)
            ->orderBy('created_at')
            ->get();

        $curIdx  = $logs->isEmpty()
            ? 0
            : (array_search($logs->last()->shift, $order) + 1) % 3;
        $nextIdx = ($curIdx + 1) % 3;

        $asgs = NocShiftAssignment::where('date', $today)
            ->whereIn('shift', [$order[$curIdx], $order[$nextIdx]])
            ->with('user')
            ->get()
            ->keyBy('shift');

        $currAsg = $asgs->get($order[$curIdx]);
        $nextAsg = $asgs->get($order[$nextIdx]);

        return response()->json([
            'current' => [
                'shift'      => $order[$curIdx],
                'start_time' => optional($currAsg?->start_time)->format('H:i') ?? '—',
                'end_time'   => optional($currAsg?->end_time)->format('H:i') ?? '—',
                'user'       => optional($currAsg?->user)->name ?? '—',
                'contact'    => optional($currAsg?->user)->phone ?? '—',
            ],
            'next' => [
                'shift'      => $order[$nextIdx],
                'start_time' => optional($nextAsg?->start_time)->format('H:i') ?? '—',
                'end_time'   => optional($nextAsg?->end_time)->format('H:i') ?? '—',
                'user'       => optional($nextAsg?->user)->name ?? '—',
                'contact'    => optional($nextAsg?->user)->phone ?? '—',
            ],
        ]);
    }

    public function apiHistory(string $date = null): JsonResponse
    {
        $day = $date ?: Carbon::now()->toDateString();

        $logs = HandoverLog::with(['fromUser', 'toUser'])
            ->where('date', $day)
            ->orderBy('created_at')
            ->get()
            ->map(fn($l) => [
                'shift'     => $l->shift,
                'from'      => optional($l->fromUser)->name ?? '—',
                'to'        => optional($l->toUser)->name ?? '—',
                'issues'    => $l->issues,
                'notes'     => $l->notes,
                'timestamp' => optional($l->created_at)->format('Y-m-d H:i') ?? '',
            ]);

        return response()->json($logs);
    }

    /**
     * Format pesan handover untuk WhatsApp
     */
public static function formatHandoverMessage(HandoverLog $handover): string
{
    $from   = optional($handover->fromUser)->name  ?? '—';
    $to     = optional($handover->toUser)->name    ?? '—';
    $issues = strip_tags($handover->issues ?? '');
    $issues = trim(preg_replace("/\r\n|\r|\n/", "\n- ", $issues));
    $issues = ltrim($issues, "- ");
    $notes  = trim(strip_tags($handover->notes ?? ''));

    return "[HANDOVER SHIFT]\n" .
           "Shift   : {$handover->shift}\n" .
           "From    : {$from}\n" .
           "To      : {$to}\n\n" .
           "Issues:\n- {$issues}\n" .
           ($notes ? "\nNotes: {$notes}\n" : '') .
           "\nNOC Command Center";
}
}
