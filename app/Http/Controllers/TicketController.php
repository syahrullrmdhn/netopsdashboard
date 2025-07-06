<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with(['customer.supplier', 'user', 'updates'])
            ->latest('open_date')
            ->paginate(15);

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        // Kalau butuh data customers di form, silakan load dan lempar ke view
        // $customers = \App\Models\Customer::orderBy('customer')->get();
        // return view('tickets.create', compact('customers'));
        return view('tickets.create');
    }

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

        $data['user_id']      = auth()->id();
        $data['alert']        = $request->has('alert');
        $data['sla_duration'] = 0;

        // Ticket number otomatis diisi di Model
        $ticket = Ticket::create($data);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket created.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['customer.supplier', 'user', 'updates.user']);
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

        return back()->with('success', 'Ticket updated.');
    }

    public function close(Ticket $ticket)
    {
        $ticket->update(['end_time' => now()]);
        return back()->with('success', 'Ticket closed.');
    }

    /**
     * Generate PDF RFO
     */
    public function rfoPdf(Request $request, Ticket $ticket)
    {
        $ticket->load(['customer.supplier', 'user', 'updates.user']);

        // Override dengan value terbaru dari modal jika ada
        $ticket->problem_detail    = $request->input('problem_detail',    $ticket->problem_detail);
        $ticket->action_taken      = $request->input('action_taken',      $ticket->action_taken);
        $ticket->preventive_action = $request->input('preventive_action', $ticket->preventive_action);

        $pdf = Pdf::loadView('tickets.rfo-pdf', compact('ticket'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("RFO-{$ticket->ticket_number}.pdf");
    }

    // Tambahan: jika ada method escalate, update, dsb
    // public function escalate(Request $request, Ticket $ticket) { ... }
}
