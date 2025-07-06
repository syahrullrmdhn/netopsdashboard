<?php

namespace App\Http\Controllers;

use App\Models\EscalationLevel;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketEscalation;

class EscalationController extends Controller
{
    public function index()
    {
        $levels = EscalationLevel::orderBy('level')->get();
        return view('escalations.index', compact('levels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'levels.*.label' => 'required|string',
            'levels.*.name'  => 'required|string',
            'levels.*.phone' => 'nullable|string',
            'levels.*.email' => 'required|email',
        ]);

        foreach ($data['levels'] as $lvl => $attrs) {
            EscalationLevel::updateOrCreate(
                ['level' => $lvl],
                [
                    'label' => $attrs['label'],
                    'name'  => $attrs['name'],
                    'phone' => $attrs['phone'],
                    'email' => $attrs['email'],
                ]
            );
        }

        return back()->with('success','Escalation settings updated.');
    }
    public function send(Request $req, Ticket $ticket)
    {
        $req->validate([
            'level' => 'required|integer|exists:escalation_levels,level'
        ]);

        $lvl = EscalationLevel::find($req->level);

        // Kirim email via Mailable
        Mail::to($lvl->email)
            ->send(new TicketEscalation($ticket, $lvl));

        return back()->with('success','Escalation email sent to '.$lvl->name);
    }
}
