<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class SLAController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $dateFrom = $request->input('date_from') ?: now()->startOfMonth()->toDateString();
        $dateTo   = $request->input('date_to')   ?: now()->endOfMonth()->toDateString();

        $customerQuery = Customer::on('customerdb');
        if ($search) {
            $customerQuery->where(function($q) use ($search) {
                $q->where('customer', 'like', "%{$search}%")
                  ->orWhere('cid_abh', 'like', "%{$search}%");
            });
        }
        $customers = $customerQuery->get();
        $customerIds = $customers->pluck('id')->toArray();

        $linkDownTickets = Ticket::query()
            ->whereIn('customer_id', $customerIds)
            ->whereRaw("LOWER(issue_type) LIKE ?", ['%link down%'])
            ->whereDate('start_time', '>=', $dateFrom)
            ->whereDate('start_time', '<=', $dateTo)
            ->get();

        $start = Carbon::parse($dateFrom)->startOfDay();
        $end   = Carbon::parse($dateTo)->endOfDay();
        $periodSec = $end->diffInSeconds($start);

        $customerStats = [];
        foreach ($customers as $cust) {
            $slaTarget = floatval($cust->sla ?: 99.5);
            $myTickets = $linkDownTickets->where('customer_id', $cust->id);

            $totalDowntime = $myTickets->sum(function($t) {
                return ($t->start_time)
                    ? Carbon::parse($t->end_time ?: now())->diffInSeconds(Carbon::parse($t->start_time))
                    : 0;
            });

            $slaAllowed = $periodSec * (100 - $slaTarget) / 100;

            // SLA realtime: 100% kalau tidak ada downtime
            if ($totalDowntime == 0) {
                $slaReal = 100.0;
            } else {
                $slaReal = max($slaTarget - round(($totalDowntime / $periodSec) * 100, 2), 0);
                $slaReal = min($slaReal, 100);
            }

            $mttr = $myTickets->count()
                ? intdiv($totalDowntime, $myTickets->count())
                : 0;

            $customerStats[] = [
                'customer'        => $cust,
                'sla_percent'     => $slaTarget,
                'total_downtime'  => $totalDowntime,
                'sla_realtime'    => $slaReal,
                'sla_allowed'     => $slaAllowed,
                'linkdown_count'  => $myTickets->count(),
                'tickets'         => $myTickets->values(),
                'mttr'            => $mttr,
            ];
        }

        // PAGINATION (manual untuk array!)
        $page    = $request->input('page', 1);
        $perPage = 15;
        $offset  = ($page - 1) * $perPage;
        $paginatedCustomerStats = new LengthAwarePaginator(
            array_slice($customerStats, $offset, $perPage),
            count($customerStats),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        return view('sla.index', [
            'customerStats' => $paginatedCustomerStats,
            'search'        => $search,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
            'periodSec'     => $periodSec,
        ]);
    }

    public function export(Request $request)
    {
        $customerId = $request->input('customer_id');
        $groupId    = $request->input('group_id');
        $dateFrom   = $request->input('date_from') ?: now()->startOfMonth()->toDateString();
        $dateTo     = $request->input('date_to')   ?: now()->endOfMonth()->toDateString();

        $customersQuery = Customer::on('customerdb');
        if ($customerId) {
            $customersQuery->where('id', $customerId);
        } elseif ($groupId) {
            $customersQuery->where('customer_group_id', $groupId);
        }
        $customers = $customersQuery->get();
        $customerIds = $customers->pluck('id')->toArray();

        $tickets = Ticket::query()
            ->whereIn('customer_id', $customerIds)
            ->whereRaw("LOWER(issue_type) LIKE ?", ['%link down%'])
            ->whereDate('start_time', '>=', $dateFrom)
            ->whereDate('start_time', '<=', $dateTo)
            ->orderBy('start_time')
            ->get();

        $start = Carbon::parse($dateFrom)->startOfDay();
        $end   = Carbon::parse($dateTo)->endOfDay();
        $periodSec = $end->diffInSeconds($start);

        $data = [];
        foreach ($customers as $cust) {
            $slaTarget = floatval($cust->sla ?: 99.5);
            $myTickets = $tickets->where('customer_id', $cust->id);

            $totalDowntime = $myTickets->sum(function($t) {
                return ($t->start_time)
                    ? Carbon::parse($t->end_time ?: now())->diffInSeconds(Carbon::parse($t->start_time))
                    : 0;
            });

            // SLA realtime: 100% kalau tidak ada downtime
            if ($totalDowntime == 0) {
                $slaReal = 100.0;
            } else {
                $slaReal = max($slaTarget - round(($totalDowntime / $periodSec) * 100, 2), 0);
                $slaReal = min($slaReal, 100);
            }

            $mttr = $myTickets->count()
                ? intdiv($totalDowntime, $myTickets->count())
                : 0;

            $data[] = [
                'customer'        => $cust,
                'sla_target'      => $slaTarget,
                'sla_real'        => $slaReal,
                'total_downtime'  => $totalDowntime,
                'tickets'         => $myTickets->values(),
                'mttr'            => $mttr,
            ];
        }

        $group = $groupId ? CustomerGroup::on('customerdb')->find($groupId) : null;
        $pdf = Pdf::loadView('sla.export_pdf', [
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'group' => $group,
        ])->setPaper('a4', 'landscape');

        $name = "SLA_Customer_" . now()->format('Ymd_His') . ".pdf";
        return $pdf->download($name);
    }
}
