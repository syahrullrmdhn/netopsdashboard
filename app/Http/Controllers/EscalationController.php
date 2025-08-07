<?php

namespace App\Http\Controllers;

use App\Models\EscalationLevel;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketEscalation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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

    $lvl = EscalationLevel::findOrFail($req->level);

    // ──── 1) Decode & extract a nice customer name ────
    $rawCust = $ticket->customer;
    $customerName = '—';

    if ($rawCust) {
        $decoded = @json_decode($rawCust, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // pick whichever you prefer:
            $customerName = $decoded['customer']
                          ?? $decoded['cid_abh']
                          ?? $rawCust;
        } else {
            $customerName = $rawCust;  // not JSON, just a string
        }
    }

    // ──── 2) Build your WhatsApp message ────
    $now = now()->format('Y-m-d H:i');
    $message = "🔔 *Ticket Escalation Notification* 🔔\n\n";
    $message .= "📋 *Ticket Number:* #{$ticket->ticket_number}\n";
    $message .= "⚙️ *Escalation Level:* {$lvl->label} (Level {$lvl->level})\n";
    $message .= "👤 *Assigned To:* {$lvl->name}\n";
    $message .= "📅 *Date & Time:* {$now}\n\n";
    $message .= "📌 *Ticket Details*\n";
    $message .= "----------------------------\n";
    $message .= "• *Status:* {$ticket->status}\n";
    $message .= "• *Customer:* {$customerName}\n";
    $message .= "• *Issue Description:*\n{$ticket->problem_detail}\n\n";
    $message .= "Please acknowledge receipt of this escalation and provide an estimated resolution timeline.\n";
    $message .= "For any questions, contact the support team immediately.";

    // ──── 3) Send via WhatsApp‐bot ────
    Http::post(env('WA_BOT_URL').'/send', [
        'to'      => Str::endsWith($lvl->phone, '@c.us')
                   ? $lvl->phone
                   : "{$lvl->phone}@c.us",
        'message' => $message,
    ])->throw();

    return back()->with('success', "Escalation successfully sent to {$lvl->name} via WhatsApp");
}

}
