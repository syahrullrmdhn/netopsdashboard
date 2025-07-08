<?php
// File: app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // ── CUSTOMER STATS ──────────────────────────────
        $totalCust   = Customer::count();

        // ── DOWN TICKETS (Open only) ────────────────────
        $downTickets = Ticket::where('issue_type', 'like', '%Down%')
                              ->whereNull('end_time')
                              ->count();

        // ── TICKET STATS ────────────────────────────────
        $openTickets = Ticket::whereNull('end_time')->count();
        $warnTickets = Ticket::where('alert', true)->count();

        // Recent tickets (10 terbaru)
        $recent = Ticket::with('customer')
                        ->latest('open_date')
                        ->limit(10)
                        ->get();

        // ── GROWTH DATA CUSTOMER ────────────────────────
        $monthlyData      = Customer::selectRaw("DATE_FORMAT(start_date, '%b') as label, COUNT(*) as value")
                                    ->whereNotNull('start_date')
                                    ->groupBy('label')
                                    ->orderByRaw('MIN(start_date)')
                                    ->pluck('value','label');

        $quarterlyData    = Customer::selectRaw("CONCAT('Q', QUARTER(start_date)) as label, COUNT(*) as value")
                                    ->whereNotNull('start_date')
                                    ->groupBy('label')
                                    ->orderByRaw('MIN(start_date)')
                                    ->pluck('value','label');

        $yearlyData       = Customer::selectRaw("YEAR(start_date) as label, COUNT(*) as value")
                                    ->whereNotNull('start_date')
                                    ->groupBy('label')
                                    ->orderBy('label')
                                    ->pluck('value','label');

        // ── GROWTH DATA TICKET ──────────────────────────
        $monthlyTickets   = Ticket::selectRaw("DATE_FORMAT(open_date, '%b') as label, COUNT(*) as value")
                                    ->whereNotNull('open_date')
                                    ->groupBy('label')
                                    ->orderByRaw('MIN(open_date)')
                                    ->pluck('value','label');

        $quarterlyTickets = Ticket::selectRaw("CONCAT('Q', QUARTER(open_date)) as label, COUNT(*) as value")
                                    ->whereNotNull('open_date')
                                    ->groupBy('label')
                                    ->orderByRaw('MIN(open_date)')
                                    ->pluck('value','label');

        $yearlyTickets    = Ticket::selectRaw("YEAR(open_date) as label, COUNT(*) as value")
                                    ->whereNotNull('open_date')
                                    ->groupBy('label')
                                    ->orderBy('label')
                                    ->pluck('value','label');

        // ── RETURN TO DASHBOARD VIEW ────────────────────
        return view('dashboard.index', compact(
            'totalCust',
            'downTickets',
            'openTickets',
            'warnTickets',
            'recent',
            'monthlyData',
            'quarterlyData',
            'yearlyData',
            'monthlyTickets',
            'quarterlyTickets',
            'yearlyTickets'
        ));
    }
}