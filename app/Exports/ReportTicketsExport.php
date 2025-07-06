<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class ReportTicketsExport implements FromCollection, WithHeadings
{
    protected $from;
    protected $to;

    /**
     * @param  \Carbon\Carbon  $from
     * @param  \Carbon\Carbon  $to
     */
    public function __construct(Carbon $from, Carbon $to)
    {
        // make sure we include the whole day
        $this->from = $from->startOfDay();
        $this->to   = $to->endOfDay();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Ticket::with(['user','customer'])
            ->whereBetween('open_date', [$this->from, $this->to])
            ->get()
            ->map(function($t) {
                return [
                    $t->ticket_number,
                    $t->open_date->format('Y-m-d H:i'),
                    optional($t->user)->name,
                    optional($t->customer)->customer,
                    optional($t->customer)->cid_abh,
                    optional($t->start_time)?->format('Y-m-d H:i') ?? '-',
                    optional($t->end_time)?->format('Y-m-d H:i') ?? '-',
                    $t->issue_type,
                    $t->service_detail,
                    $t->alert ? 'Yes' : 'No',
                ];
            });
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Ticket Number',
            'Open Date',
            'Opened By',
            'Customer',
            'CID Customer',
            'Start Time',
            'End Time',
            'Issue Type',
            'Service Detail',
            'Alert',
        ];
    }
}
