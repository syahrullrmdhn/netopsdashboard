<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketUpdateController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'detail' => 'required|string',
        ]);

        $ticket->updates()->create([
            'detail'  => $data['detail'],
            'user_id' => auth()->id(),
        ]);

        return back()->with('success','Detail added.');
    }
}
