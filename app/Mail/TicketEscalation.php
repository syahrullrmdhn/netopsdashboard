<?php
namespace App\Mail;

use App\Models\Ticket;
use App\Models\EscalationLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketEscalation extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket, $level;

    public function __construct(Ticket $ticket, EscalationLevel $level)
    {
        $this->ticket = $ticket;
        $this->level  = $level;
    }

public function build()
{
    $mailFrom = \App\Models\EmailSetting::first();
    if (!$mailFrom) {
        // fallback supaya tidak error, bisa pakai .env atau beri pesan
        abort(500, 'Please set Email Sender in Email Settings menu.');
    }

    return $this
        ->from($mailFrom->from_address, $mailFrom->from_name)
        ->subject("Escalation: {$this->level->label} (Ticket #{$this->ticket->ticket_number})")
        ->view('emails.ticket-escalation')
        ->with([
            'ticket' => $this->ticket,
            'level'  => $this->level,
            'user'   => auth()->user(),
        ]);
}

}
