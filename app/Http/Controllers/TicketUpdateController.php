<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketUpdate;
use Carbon\Carbon;

class TicketUpdateController extends Controller
{
    /**
     * Store a new update entry for a ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ticket        $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'detail' => 'required|string',
        ]);

        $ticket->updates()->create([
            'detail'  => $data['detail'],
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Detail added.');
    }

    /**
     * Update an existing chronology entry (text + timestamp).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ticket        $ticket
     * @param  \App\Models\TicketUpdate  $update
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Ticket $ticket, TicketUpdate $update)
    {
        $data = $request->validate([
            'detail'    => 'required|string',
            'timestamp' => 'required|date',
        ]);

        // Ensure this update belongs to this ticket
        if ($update->ticket_id !== $ticket->id) {
            abort(403);
        }

        // Apply new detail
        $update->detail = $data['detail'];

        // Temporarily disable auto‑timestamps so we can set our own
        $update->timestamps = false;
        $dt = Carbon::parse($data['timestamp']);
        $update->created_at = $dt;
        $update->updated_at = $dt;
        $update->save();
        $update->timestamps = true;

        return back()->with('success', 'Chronology entry updated.');
    }
}
