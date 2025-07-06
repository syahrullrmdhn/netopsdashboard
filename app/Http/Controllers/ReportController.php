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
     * Tampilkan form laporan dengan filter periode, customer & customer group.
     */
    public function index()
    {
        $customers = Customer::select(['id','customer','cid_abh'])->get();
        $groups    = CustomerGroup::select(['id','group_name'])->get();

        return view('reports.index', compact('customers','groups'));
    }

    /**
     * Export Excel via Box\Spout, dengan optional filter customer / group.
     */
    public function exportSpout(Request $request)
    {
        $from = Carbon::parse($request->query('start_date'))->startOfDay();
        $to   = Carbon::parse($request->query('end_date'))->endOfDay();

        $customerId = $request->query('customer_id');
        $groupId    = $request->query('group_id');

        // 1) Daftar customer sesuai filter
        $custQ = Customer::select(['id','customer','cid_abh']);
        if ($customerId) {
            $custQ->where('id', $customerId);
        }
        if ($groupId) {
            $custQ->where('customer_group_id', $groupId);
        }
        $customers = $custQ->get();

        // 2) Ambil tiket dengan eager‐load relasi supplier
        $ticketQ = Ticket::with('customer.supplier')
            ->whereBetween('open_date', [$from, $to]);
        if ($customerId) {
            $ticketQ->where('customer_id', $customerId);
        }
        if ($groupId) {
            $ids = $customers->pluck('id')->toArray();
            $ticketQ->whereIn('customer_id', $ids);
        }
        $tickets = $ticketQ->orderBy('open_date')
                          ->get()
                          ->groupBy('customer_id');

        // 3) Buat writer Excel
        $writer   = WriterEntityFactory::createXLSXWriter();
        $fileName = "Laporan_Tiket_{$from->format('Ymd')}_{$to->format('Ymd')}.xlsx";
        $writer->openToBrowser($fileName);

        // 4) Definisikan header kolom
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

        // 5) Tambahkan identitas PT. Abhinawa Sumberdaya Asia
        $writer->addRow(WriterEntityFactory::createRowFromArray([
            'PT. Abhinawa Sumberdaya Asia'
        ]));
        $writer->addRow(WriterEntityFactory::createRowFromArray([
            'Head Office: Menara Kadin Indonesia, Jl. H. R. Rasuna Said, RT.1/RW.2, Kuningan, Kuningan Tim., Kecamatan Setiabudi, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12950'
        ]));
        // baris kosong (spasi) sebelum header
        $writer->addRow(WriterEntityFactory::createRowFromArray(
            array_fill(0, count($header), '')
        ));

        // 6) Tulis header kolom
        $writer->addRow(WriterEntityFactory::createRowFromArray($header));

        // 7) Isi data per‐customer
        foreach ($customers as $cust) {
            $custTickets = $tickets->get($cust->id, collect());

            if ($custTickets->isEmpty()) {
                // baris jika tidak ada tiket
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
     * Export PDF via DOMPDF, dengan optional filter customer / group.
     */
    public function exportPdf(Request $request)
    {
        $from = Carbon::parse($request->query('start_date'))->startOfDay();
        $to   = Carbon::parse($request->query('end_date'))->endOfDay();

        $customerId = $request->query('customer_id');
        $groupId    = $request->query('group_id');

        // daftar customer sesuai filter
        $custQ = Customer::select(['id','customer','cid_abh']);
        if ($customerId) {
            $custQ->where('id', $customerId);
        }
        if ($groupId) {
            $custQ->where('customer_group_id', $groupId);
        }
        $customers = $custQ->get();

        // tiket dengan eager‐load relasi supplier
        $ticketQ = Ticket::with('customer.supplier')
            ->whereBetween('open_date', [$from, $to]);
        if ($customerId) {
            $ticketQ->where('customer_id', $customerId);
        }
        if ($groupId) {
            $ids = $customers->pluck('id')->toArray();
            $ticketQ->whereIn('customer_id', $ids);
        }
        $ticketsByCust = $ticketQ->orderBy('open_date')
                                ->get()
                                ->groupBy('customer_id');

        // render PDF
        $pdf = Pdf::loadView('reports.pdf', compact(
            'customers','ticketsByCust','from','to'
        ))
        ->setPaper('a4','landscape');

        $fileName = "Laporan_Tiket_{$from->format('Ymd')}_{$to->format('Ymd')}.pdf";
        return $pdf->download($fileName);
    }
}
