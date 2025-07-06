<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\Customer;
use App\Models\TicketUpdate;
use Carbon\Carbon;

class PerformanceController extends Controller
{
    /**
     * Summary page: links + mini recap chart
     */
  public function index()
{
    // 1) Recap 6 bulan terakhir
    $recap = Ticket::select(
            DB::raw("DATE_FORMAT(open_date,'%b %Y') as bulan"),
            DB::raw("COUNT(*) as total")
        )
        ->where('open_date', '>=', now()->subMonths(6)->startOfMonth())
        ->groupBy(DB::raw("DATE_FORMAT(open_date,'%b %Y')"))
        ->orderBy(DB::raw("MIN(open_date)"))
        ->get();

    $recapLabels = $recap->pluck('bulan');
    $recapData   = $recap->pluck('total');

    // 2) Hitung total tiket
    $total = Ticket::count();

    // 3) Responsiveness ≤15m
    $quickIds = TicketUpdate::join('tickets','ticket_updates.ticket_id','=','tickets.id')
        ->whereRaw('TIMESTAMPDIFF(MINUTE, tickets.open_date, ticket_updates.created_at) <= 15')
        ->groupBy('ticket_id')
        ->pluck('ticket_id');
    $responsiveness = $total
        ? round(Ticket::whereIn('id', $quickIds)->count() / $total * 100, 1)
        : 0;

    // 4) Availability ≤7m downtime
    $closed = Ticket::whereNotNull('end_time')->count();
    $availableCount = Ticket::whereNotNull('start_time')
        ->whereNotNull('end_time')
        ->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, end_time) <= ?', [7])
        ->count();
    $availability = $closed
        ? round($availableCount / $closed * 100, 1)
        : 0;

    // 5) Reliability = closed / total
    $reliability = $total ? round($closed / $total * 100, 1) : 0;

    // 6) Quality >1 update
    $manyIds = TicketUpdate::groupBy('ticket_id')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('ticket_id');
    $quality = $total
        ? round(Ticket::whereIn('id', $manyIds)->count() / $total * 100, 1)
        : 0;

    // 7) Utilization: perbandingan tiket bulan ini vs bulan tertinggi dalam 6 bulan
    $current  = $recap->last()->total ?? 0;
    $max      = $recap->max('total') ?: 1;
    $utilization = round($current / $max * 100, 1);

    // Kirim semua variabel ke view
    return view('performance.index', compact(
        'recapLabels','recapData',
        'reliability','availability','responsiveness','quality','utilization'
    ));
}

    /**
     * Dashboard evaluasi dengan 4 chart dinamis
     */
    public function evalDashboard()
    {
        // --- 1) Bar chart: total tickets per bulan (6 bln terakhir) ---
        $recap = Ticket::select(
                DB::raw("DATE_FORMAT(open_date,'%b %Y') as bulan"),
                DB::raw("COUNT(*) as total")
            )
            ->where('open_date', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy(DB::raw("DATE_FORMAT(open_date,'%b %Y')"))
            ->orderBy(DB::raw("MIN(open_date)"))
            ->get();
        $recapLabels = $recap->pluck('bulan');
        $recapData   = $recap->pluck('total');

        // --- 2) KPI metrics ---
        $totalTickets = Ticket::count();

        // Responsiveness ≤15m
        $quickIds = TicketUpdate::select('ticket_id')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, tickets.open_date, ticket_updates.created_at) <= 15')
            ->join('tickets','ticket_updates.ticket_id','=','tickets.id')
            ->groupBy('ticket_id')
            ->pluck('ticket_id');
        $quickCount = Ticket::whereIn('id',$quickIds)->count();
        $responsiveness = $totalTickets ? round($quickCount/$totalTickets*100,1) : 0;

        // Availability ≤7m downtime
        $closedCount = Ticket::whereNotNull('end_time')->count();
        $withinSlaCount = Ticket::whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, end_time) <= ?', [7])
            ->count();
        $availability = $closedCount ? round($withinSlaCount/$closedCount*100,1) : 0;

        // Reliability = closed / total
        $reliability = $totalTickets ? round($closedCount/$totalTickets*100,1) : 0;

        // Quality >1 update
        $manyIds = TicketUpdate::select('ticket_id')
            ->groupBy('ticket_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('ticket_id');
        $manyCount = Ticket::whereIn('id',$manyIds)->count();
        $quality = $totalTickets ? round($manyCount/$totalTickets*100,1) : 0;

        $perfLabels = ['Reliability','Availability','Quality','Responsiveness'];
        $perfData   = [$reliability,$availability,$quality,$responsiveness];

        // --- 3) Avg response per minggu (bulan ini) ---
        $start = now()->startOfMonth();
        $opLabels = []; $opData = [];
        for($i=0;$i<4;$i++){
            $from = $start->copy()->addWeeks($i);
            $to   = $start->copy()->addWeeks($i+1);
            $opLabels[] = 'Week '.($i+1);
            $avg = Ticket::whereBetween('open_date',[$from,$to])
                ->join('ticket_updates','tickets.id','=','ticket_updates.ticket_id')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, tickets.open_date, ticket_updates.created_at)) as avg_response'))
                ->first()->avg_response ?? 0;
            $opData[] = round($avg,1);
        }

        // --- 4) Utilization top 8 customers bulan ini ---
        $tixCounts = Ticket::select('customer_id', DB::raw('COUNT(*) as cnt'))
            ->whereBetween('open_date',[now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('customer_id')
            ->orderByDesc('cnt')
            ->take(8)
            ->get();
        $customerIds = $tixCounts->pluck('customer_id')->toArray();
        $customers   = Customer::whereIn('id',$customerIds)
                        ->get()->keyBy('id');
        $maxCnt      = $tixCounts->max('cnt') ?: 1;
        $utilLabels  = [];
        $utilData    = [];
        foreach($tixCounts as $row){
            $cust = $customers[$row->customer_id] ?? null;
            $utilLabels[] = $cust ? $cust->customer : 'ID-'.$row->customer_id;
            $utilData[]   = round($row->cnt / $maxCnt * 100, 1);
        }

        return view('performance.eval', compact(
            'recapLabels','recapData',
            'perfLabels','perfData',
            'opLabels','opData',
            'utilLabels','utilData'
        ));
    }

    /**
     * Detail per metric
     */
    public function detail($type)
    {
        $title=''; $desc=''; $data=collect();

        switch($type){
            case 'reliability':
                $title='Reliability – Closed Tickets';
                $desc='Daftar tiket yang sudah ditutup.';
                $data=Ticket::whereNotNull('end_time')->orderBy('end_time','desc')->get();
                break;

            case 'availability':
                $title='Availability – Downtime ≤7m';
                $desc='Tiket closed dengan downtime ≤7 menit.';
                $data=Ticket::whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, end_time) <= ?', [7])
                    ->orderBy('end_time','desc')
                    ->get();
                break;

            case 'responsiveness':
                $title='Responsiveness – Update ≤15m';
                $desc='Tiket yang mendapat update pertama dalam ≤15 menit.';
                $many = TicketUpdate::select('ticket_id')
                    ->whereRaw('TIMESTAMPDIFF(MINUTE, tickets.open_date, ticket_updates.created_at) <= 15')
                    ->join('tickets','ticket_updates.ticket_id','=','tickets.id')
                    ->groupBy('ticket_id')
                    ->pluck('ticket_id');
                $data = Ticket::with('updates')->whereIn('id',$many)->get();
                break;

            case 'quality':
                $title='Quality – >1 Update';
                $desc='Tiket dengan lebih dari satu kronologi/update.';
                $many = TicketUpdate::select('ticket_id')
                    ->groupBy('ticket_id')
                    ->havingRaw('COUNT(*) > 1')
                    ->pluck('ticket_id');
                $data = Ticket::with('updates')->whereIn('id',$many)->get();
                break;

            case 'utilization':
                $title='Utilization – Top Customers';
                $desc='Customer dengan tiket terbanyak bulan ini.';
                // reuse tixCounts logic
                $tixCounts = Ticket::select('customer_id', DB::raw('COUNT(*) as cnt'))
                    ->whereBetween('open_date',[now()->startOfMonth(), now()->endOfMonth()])
                    ->groupBy('customer_id')
                    ->orderByDesc('cnt')
                    ->take(8)
                    ->get();
                $custIds = $tixCounts->pluck('customer_id')->toArray();
                $custs   = Customer::whereIn('id',$custIds)->get()->keyBy('id');
                $data = $tixCounts->map(fn($r)=>[
                    'name'  => $custs[$r->customer_id]->customer ?? 'ID-'.$r->customer_id,
                    'count' => $r->cnt
                ]);
                break;

            default:
                abort(404);
        }

        return view('performance.detail', compact('type','title','desc','data'));
    }
}
