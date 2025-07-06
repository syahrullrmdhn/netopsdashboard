<?php

namespace App\Http\Controllers;

use App\Models\NocShiftAssignment;
use App\Models\HandoverLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NocController extends Controller
{
    public function manageShifts()
    {
        $users       = User::orderBy('name')->get();
        $today       = now()->toDateString();
        $assignments = NocShiftAssignment::where('date', $today)
            ->get()->keyBy('shift');

        return view('noc.manage-shifts', compact('users','assignments','today'));
    }

    public function updateShifts(Request $request)
    {
        $today = now()->toDateString();
        foreach (['pagi','siang','malam'] as $shift) {
            $uid = $request->input("assignment.$shift");
            if (! $uid) continue;
            NocShiftAssignment::updateOrCreate(
                ['date'=>$today,'shift'=>$shift],
                ['user_id'=>$uid]
            );
        }
        return back()->with('success','Assignments updated.');
    }

    public function handover()
    {
        $today = now()->toDateString();

        // ambil semua log hari ini, urut by waktu
        $logs   = HandoverLog::where('date',$today)
                  ->orderBy('created_at')
                  ->get();

        // shift order
        $order  = ['pagi','siang','malam'];

        if ($logs->isEmpty()) {
            $cur   = 'pagi';
        } else {
            // last shift yang sudah di‐handover
            $last  = $logs->last()->shift;
            $idx   = array_search($last,$order);
            $cur   = $order[($idx + 1) % 3];
        }
        // next shift
        $nidx   = array_search($cur,$order);
        $next   = $order[($nidx + 1) % 3];

        // ambil assignment untuk cur & next
        $asgs = NocShiftAssignment::where('date',$today)
                ->whereIn('shift',[$cur,$next])
                ->with('user')
                ->get()->keyBy('shift');

        $curUser  = $asgs[$cur]->user ?? null;
        $nextUser = $asgs[$next]->user ?? null;

        // ambil tiket open + last update
        $tickets = Ticket::whereNull('end_time')
            ->with([
              'customer.supplier',
              'updates'=>fn($q)=>$q->latest()->limit(1)
            ])
            ->orderBy('open_date')
            ->get();

        // buat ringkasan markdown
        $markdown = $tickets->map(function($t) {
            $u = $t->updates->first();
            $last = $u
                ? "Update terakhir: {$u->detail} ({$u->created_at->format('d/m H:i')})"
                : 'Belum ada update.';
            return "* **#{$t->ticket_number}** - {$t->customer->customer} ({$t->issue_type}). {$last}";
        })->implode("\n");

        // view handover
        return view('noc.handover', compact(
            'cur','next','curUser','nextUser','tickets','markdown'
        ));
    }

    public function storeHandover(Request $r)
    {
        $data = $r->validate([
            'shift'      => 'required|string',
            'to_user_id' => 'nullable|exists:users,id',
            'issues'     => 'required|string',
            'notes'      => 'nullable|string',
        ]);

        HandoverLog::create([
            'date'         => now()->toDateString(),
            'shift'        => $data['shift'],
            'from_user_id' => Auth::id(),
            'to_user_id'   => $data['to_user_id'],
            'issues'       => $data['issues'],
            'notes'        => $data['notes'],
        ]);

        return back()->with('success','Handover logged successfully.');
    }

    public function history()
    {
        $today = now()->toDateString();

        // ambil semua log hari ini
        $logs = HandoverLog::with(['fromUser','toUser'])
                ->where('date',$today)
                ->orderBy('created_at')
                ->get();

        return view('noc.history', compact('logs','today'));
    }
}
