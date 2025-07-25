<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Ticket;         // default connection = networkdashboard
use App\Models\Customer;       // connection = customerdb
use App\Models\CustomerGroup;  // connection = customerdb
use Throwable;
use Illuminate\Pagination\LengthAwarePaginator;

class SLAController extends Controller
{
    protected string $host;
    protected string $user;
    protected string $passhash;

    public function __construct()
    {
        $this->host     = rtrim(config('prtg.host',''), '/');
        $this->user     = config('prtg.username','');
        $this->passhash = config('prtg.passhash','');
    }

public function index(Request $request)
{
    $search   = $request->input('search');
    $dateFrom = $request->input('date_from') ?: now()->startOfMonth()->toDateString();
    $dateTo   = $request->input('date_to')   ?: now()->endOfMonth()->toDateString();

    // Cari customer (bisa search)
    $customerQuery = \App\Models\Customer::on('customerdb');
    if ($search) {
        $customerQuery->where(function($q) use ($search) {
            $q->where('customer', 'like', "%{$search}%")
              ->orWhere('cid_abh', 'like', "%{$search}%");
        });
    }
    $customers = $customerQuery->get();
    $customerIds = $customers->pluck('id')->toArray();

    // Semua tiket link down periode ini (untuk semua customer hasil query di atas)
    $linkDownTickets = \App\Models\Ticket::query()
        ->whereIn('customer_id', $customerIds)
        ->whereRaw("LOWER(issue_type) LIKE ?", ['%link down%'])
        ->whereDate('start_time', '>=', $dateFrom)
        ->whereDate('start_time', '<=', $dateTo)
        ->get();

    // Hitung detik periode
    $start = \Carbon\Carbon::parse($dateFrom)->startOfDay();
    $end   = \Carbon\Carbon::parse($dateTo)->endOfDay();
    $periodSec = $end->diffInSeconds($start);

    // Build data customerStats
    $customerStats = [];
    foreach ($customers as $cust) {
        $slaPercent = floatval($cust->sla ?: 99.5);
        $myTickets = $linkDownTickets->where('customer_id', $cust->id);

        $totalDowntime = $myTickets->sum(function($t) {
            return ($t->start_time)
                ? \Carbon\Carbon::parse($t->end_time ?: now())->diffInSeconds(\Carbon\Carbon::parse($t->start_time))
                : 0;
        });

        $slaAllowed = $periodSec * (100 - $slaPercent) / 100;
        $slaReal = $slaPercent - round(($totalDowntime / $periodSec) * 100, 2);
        $slaReal = max($slaReal, 0); // prevent negative

        $customerStats[] = [
            'customer'        => $cust,
            'sla_percent'     => $slaPercent,
            'total_downtime'  => $totalDowntime,
            'sla_realtime'    => $slaReal,
            'sla_allowed'     => $slaAllowed,
            'linkdown_count'  => $myTickets->count(),
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
    /** Halaman detail: chart + report templates */
    public function show(int $sensorId)
    {
        // fetch sensor name
        $sensorName = "Sensor #{$sensorId}";
        try {
            $r1 = Http::get("{$this->host}/api/table.json", [
                'content'  => 'sensors',
                'columns'  => 'objid,name',
                'count'    => '*',
                'username' => $this->user,
                'passhash' => $this->passhash,
            ]);
            if ($r1->ok()) {
                foreach ($r1->json()['sensors'] as $s) {
                    if ((int)$s['objid'] === $sensorId) {
                        $sensorName = $s['name'];
                        break;
                    }
                }
            }
        } catch (\Throwable $e) { /* ignore */ }

        // fetch list of **report templates** (not scheduled reports)
        $reports = [];
        try {
            $r2 = Http::get("{$this->host}/api/table.json", [
                'content'  => 'reporttemplates',
                'columns'  => 'objid,name',
                'count'    => '*',
                'username' => $this->user,
                'passhash' => $this->passhash,
            ]);
            if ($r2->ok()) {
                $reports = $r2->json()['reporttemplates'] ?? [];
            }
        } catch (\Throwable $e) { /* ignore */ }

        return view('sla.show', compact(
            'sensorId','sensorName','reports'
        ));
    }
    public function device(string $device)
    {
        $host     = rtrim(config('prtg.host',''), '/');
        $user     = config('prtg.username','');
        $passhash = config('prtg.passhash','');

        $sensors = [];
        $error   = null;

        try {
            // ambil semua sensor
            $resp = Http::get("{$host}/api/table.json", [
                'content'  => 'sensors',
                'columns'  => 'objid,name,device,lastvalue,status,message',
                'count'    => '*',
                'username' => $user,
                'passhash' => $passhash,
            ]);

            if ($resp->ok()) {
                $all = $resp->json()['sensors'] ?? [];
                // filter hanya sensor yang device-nya sama (case‐sensitive)
                $sensors = collect($all)
                    ->filter(fn($s)=> $s['device'] === $device)
                    ->values()
                    ->all();
            } else {
                $error = "PRTG API HTTP {$resp->status()}";
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        return view('sla.device', compact('device','sensors','error'));
    }
}
