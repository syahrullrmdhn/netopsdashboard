<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Ticket;         // default connection = networkdashboard
use App\Models\Customer;       // connection = customerdb
use App\Models\CustomerGroup;  // connection = customerdb
use Throwable;

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
        //
        // 1) (Optional) PRTG sensors fetch… omit if not needed
        //
        $connected = false;
        $errorMsg  = '';
        $groupedSensors = [];
        try {
            $resp = Http::get("{$this->host}/api/table.json", [
                'content'  => 'sensors',
                'columns'  => 'objid,name,device,lastvalue,status,message',
                'count'    => '*',
                'username' => $this->user,
                'passhash' => $this->passhash,
            ]);
            if ($resp->ok()) {
                $sensors = $resp->json()['sensors'] ?? [];
                $groupedSensors = collect($sensors)
                    ->groupBy('device')
                    ->map(fn($grp)=>$grp->all())
                    ->all();
                $connected = true;
            } else {
                $errorMsg = "PRTG API HTTP {$resp->status()}";
            }
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
        }

        //
        // 2) Ambil semua ticket dari networkdashboard
        //
        $allTickets = Ticket::select('customer_id','start_time','end_time')
                             ->get()
                             ->filter(fn($t)=> $t->customer_id !== null);

        // Group per customer_id
        $ticketsByCust = $allTickets->groupBy('customer_id');

        // Daftar customer_id yang punya ticket
        $customerIds = $ticketsByCust->keys()->toArray();

        //
        // 3) Query Customer (connection customerdb) hanya yg punya ticket
        //
        $custQ = Customer::whereIn('id', $customerIds);

        // Filters: search, group_id, status
        if ($request->filled('search')) {
            $q = $request->search;
            $custQ->where(fn($qb)=>
                $qb->where('customer','like',"%{$q}%")
                   ->orWhere('cid_abh','like',"%{$q}%")
            );
        }
        if ($request->filled('group_id')) {
            $custQ->where('customer_group_id',$request->group_id);
        }
        if ($request->filled('status')) {
            $custQ->where('status',(int)$request->status);
        }

        // Paginate & append filters
        $customers = $custQ->latest('id')
                           ->paginate(15)
                           ->appends($request->query());

        // Dropdown data
        $groupsList = CustomerGroup::all();
        $statuses   = [ 1=>'Active',2=>'Pending',3=>'Suspended',4=>'Terminated' ];

        // Pass ke view
        return view('sla.index', compact(
            'connected','errorMsg','groupedSensors',
            'customers','groupsList','statuses',
            'ticketsByCust'
        ));
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
