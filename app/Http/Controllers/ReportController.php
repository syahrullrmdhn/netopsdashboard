<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Ticket;
use Carbon\Carbon;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Tampilkan form laporan dengan filter & chart summary.
     */
    public function index(Request $request)
    {
        // FILTER dari query string
        $start = $request->query('start_date', now()->subDays(30)->toDateString()); // default 7 hari terakhir
        $end   = $request->query('end_date', now()->toDateString());

        $customerId = $request->query('customer_id');
        $groupId    = $request->query('group_id');
        $issueType  = $request->query('issue_type');

        // Daftar Customer dan Group (untuk dropdown)
        $groups    = CustomerGroup::select(['id','group_name'])->get();

        // Jika filter group, hanya tampilkan customer pada group tsb
        $customerQuery = Customer::select(['id','customer','cid_abh']);
        if ($groupId) $customerQuery->where('customer_group_id', $groupId);
        $customers = $customerQuery->get();

        // Daftar unique issue_type untuk filter dropdown (ambil dari seluruh tiket)
        $issueTypes = Ticket::query()
            ->selectRaw('LOWER(TRIM(issue_type)) as issue_type')
            ->whereNotNull('issue_type')
            ->where('issue_type', '!=', '')
            ->groupByRaw('LOWER(TRIM(issue_type))')
            ->orderByRaw('LOWER(TRIM(issue_type))')
            ->pluck('issue_type')
            ->map(fn($v) => ucwords($v))
            ->unique()
            ->values();

        // ========================
        // CHART & SUMMARY DATA
        // ========================

        // Query builder dengan filter yang sama untuk semua chart & summary
        $ticketQ = Ticket::query()->whereBetween('open_date', [
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->endOfDay()
        ]);

        if ($customerId) $ticketQ->where('customer_id', $customerId);
        if ($groupId) {
            $ids = $customers->pluck('id')->toArray();
            $ticketQ->whereIn('customer_id', $ids);
        }
        if ($issueType) {
            $ticketQ->whereRaw('LOWER(TRIM(issue_type)) = ?', [strtolower(trim($issueType))]);
        }

        // Per type issue
        $chartData = [
            'by_issue_type' => $ticketQ->clone()
                ->selectRaw('LOWER(TRIM(issue_type)) as issue_type, count(*) as total')
                ->whereNotNull('issue_type')
                ->where('issue_type', '!=', '')
                ->groupByRaw('LOWER(TRIM(issue_type))')
                ->orderBy('total', 'desc')
                ->pluck('total', 'issue_type')
                ->toArray(),
            'total'         => $ticketQ->clone()->count(),
            'total_closed'  => $ticketQ->clone()->whereNotNull('end_time')->count(),
            'total_open'    => $ticketQ->clone()->whereNull('end_time')->count(),
            'per_month'     => $ticketQ->clone()
                ->selectRaw('DATE_FORMAT(open_date, "%Y-%m") as period, count(*) as total')
                ->where('open_date', '>=', Carbon::parse($start)->subMonths(12)->startOfMonth())
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('total','period')
                ->toArray(),
        ];
        $chartData['avg_open_per_month'] = $chartData['per_month']
            ? round(array_sum($chartData['per_month']) / count($chartData['per_month']), 1)
            : 0;

        return view('reports.index', compact(
            'customers', 'groups', 'issueTypes', 'chartData',
            'start', 'end', 'customerId', 'groupId', 'issueType'
        ));
    }

    /**
     * Export Excel via Box\Spout, dengan filter customer/group/issue_type.
     */
    public function exportSpout(Request $request)
    {
        $from = Carbon::parse($request->query('start_date'))->startOfDay();
        $to   = Carbon::parse($request->query('end_date'))->endOfDay();

        $customerId = $request->query('customer_id');
        $groupId    = $request->query('group_id');
        $issueType  = $request->query('issue_type');

        // Daftar customer sesuai filter
        $custQ = Customer::select(['id','customer','cid_abh']);
        if ($customerId) $custQ->where('id', $customerId);
        if ($groupId)    $custQ->where('customer_group_id', $groupId);
        $customers = $custQ->get();

        // Tiket dengan relasi supplier
        $ticketQ = Ticket::with('customer.supplier')
            ->whereBetween('open_date', [$from, $to]);
        if ($customerId) $ticketQ->where('customer_id', $customerId);
        if ($groupId) {
            $ids = $customers->pluck('id')->toArray();
            $ticketQ->whereIn('customer_id', $ids);
        }
        // FILTER BY TYPE OF ISSUE (case-insensitive, based on DB value)
        if ($issueType) {
            $ticketQ->whereRaw('LOWER(TRIM(issue_type)) = ?', [strtolower(trim($issueType))]);
        }

        $tickets = $ticketQ->orderBy('open_date')->get()->groupBy('customer_id');

        // Setup writer Excel
        $writer   = WriterEntityFactory::createXLSXWriter();
        $fileName = "Laporan_Tiket_{$from->format('Ymd')}_{$to->format('Ymd')}.xlsx";
        $writer->openToBrowser($fileName);

        $header = [
            'Customer SID',
            'Customer Name',
            'Supplier SID',
            'Supplier Name',
            'Type of Issue',
            'ABH Ticket #',
            'Supplier Ticket #',
            'Start Time',
            'End Time',
            'Duration (min)',
            'Root Cause',
            'Action Taken',
        ];
        $writer->addRow(WriterEntityFactory::createRowFromArray(['PT. Abhinawa Sumberdaya Asia']));
        $writer->addRow(WriterEntityFactory::createRowFromArray([
            'Head Office: Menara Kadin Indonesia, Jl. H. R. Rasuna Said, RT.1/RW.2, Kuningan, Kuningan Tim., Kecamatan Setiabudi, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12950'
        ]));
        $writer->addRow(WriterEntityFactory::createRowFromArray(array_fill(0, count($header), '')));
        $writer->addRow(WriterEntityFactory::createRowFromArray($header));

        // Data per-customer
        foreach ($customers as $cust) {
            $custTickets = $tickets->get($cust->id, collect());

            if ($custTickets->isEmpty()) {
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    $cust->cid_abh,
                    $cust->customer,
                    '-', '-', '-', '-', '-',
                    '-', '-', '-', '-', '-',
                ]));
            } else {
                foreach ($custTickets as $t) {
                    $start    = optional($t->start_time)->format('Y-m-d H:i') ?: '-';
                    $end      = optional($t->end_time)->format('Y-m-d H:i')   ?: '-';
                    $duration = ($t->start_time && $t->end_time)
                        ? $t->end_time->diffInMinutes($t->start_time)
                        : '-';

                    $writer->addRow(WriterEntityFactory::createRowFromArray([
                        optional($t->customer)->cid_abh,
                        optional($t->customer)->customer,
                        optional($t->customer)->cid_supp,
                        optional($t->customer->supplier)->nama_supplier,
                        $t->issue_type,
                        $t->ticket_number,
                        $t->supplier_ticket_number ?: '-',
                        $start,
                        $end,
                        $duration,
                        $t->problem_detail ?: '-',
                        $t->action_taken   ?: '-',
                    ]));
                }
            }
        }

        $writer->close();
    }

    /**
     * Export PDF via DOMPDF, dengan optional filter customer/group/issue_type.
     */
    public function exportPdf(Request $request)
    {
        $from = Carbon::parse($request->query('start_date'))->startOfDay();
        $to   = Carbon::parse($request->query('end_date'))->endOfDay();

        $customerId = $request->query('customer_id');
        $groupId    = $request->query('group_id');
        $issueType  = $request->query('issue_type');

        // Daftar customer sesuai filter
        $custQ = Customer::select(['id','customer','cid_abh']);
        if ($customerId) $custQ->where('id', $customerId);
        if ($groupId)    $custQ->where('customer_group_id', $groupId);
        $customers = $custQ->get();

        // Tiket dengan relasi supplier
        $ticketQ = Ticket::with('customer.supplier')
            ->whereBetween('open_date', [$from, $to]);
        if ($customerId) $ticketQ->where('customer_id', $customerId);
        if ($groupId) {
            $ids = $customers->pluck('id')->toArray();
            $ticketQ->whereIn('customer_id', $ids);
        }
        // FILTER BY TYPE OF ISSUE (case-insensitive, based on DB value)
        if ($issueType) {
            $ticketQ->whereRaw('LOWER(TRIM(issue_type)) = ?', [strtolower(trim($issueType))]);
        }

        $ticketsByCust = $ticketQ->orderBy('open_date')->get()->groupBy('customer_id');

        $pdf = Pdf::loadView('reports.pdf', compact(
            'customers','ticketsByCust','from','to'
        ))->setPaper('a4','landscape');

        $fileName = "Laporan_Tiket_{$from->format('Ymd')}_{$to->format('Ymd')}.pdf";
        return $pdf->download($fileName);
    }
}
