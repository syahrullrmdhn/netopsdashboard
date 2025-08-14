<?php

namespace App\Http\Controllers;

use App\Services\ObserviumClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    public function index()
    {
        return view('monitoring.index');
    }

    public function devicesJson(Request $req, ObserviumClient $obs)
    {
        try {
            $list = $obs->devicesViaHtml();

            // hydrate detail (OS/sysname/hardware/location/if_count)
            // batasi biar ringan
            $max   = (int) $req->integer('max', 30);
            $slice = array_slice($list, 0, max(1,$max));

            $devices = [];
            foreach ($slice as $row) {
                $d = $obs->deviceViaHtml($row['device_id']);
                // merge nama & status dari list (lebih cepat)
                $d['hostname'] = $row['hostname'] ?: $d['hostname'];
                $d['status']   = $row['status'];
                $devices[]     = $d;
            }

            // filter q
            $q = trim((string) $req->get('q', ''));
            if ($q !== '') {
                $qq = mb_strtolower($q);
                $devices = array_values(array_filter($devices, function ($d) use ($qq) {
                    return str_contains(mb_strtolower($d['hostname']), $qq)
                        || str_contains(mb_strtolower($d['location'] ?? ''), $qq)
                        || str_contains(mb_strtolower($d['os'] ?? ''), $qq)
                        || str_contains(mb_strtolower($d['hardware'] ?? ''), $qq)
                        || str_contains(mb_strtolower($d['sysname'] ?? ''), $qq);
                }));
            }

            $total = count($devices);
            $up    = count(array_filter($devices, fn($d) => (int)$d['status'] === 1));
            $down  = $total - $up;

            return response()->json(compact('total','up','down','devices'));
        } catch (\Throwable $e) {
            Log::error('observium.devicesJson', ['e'=>$e]);
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function interfacesJson(Request $req, ObserviumClient $obs)
    {
        try {
            $deviceId = $req->get('device_id');
            $limit    = max(1, (int)$req->integer('limit', 50));
            $q        = trim((string)$req->get('q',''));

            $items = [];

            // jika ada device_id: ambil langsung
            if ($deviceId) {
                $ifaces = $obs->interfacesViaHtml($deviceId);
                $host   = $obs->deviceViaHtml($deviceId)['hostname'] ?? "#$deviceId";
                foreach ($ifaces as $if) {
                    if ($q !== '' && !str_contains(mb_strtolower($if['name']), mb_strtolower($q))) continue;
                    $items[] = [
                        'device_id' => (int)$deviceId,
                        'hostname'  => $host,
                        'ifname'    => $if['name'],
                        'port_id'   => $if['id'],
                        'status'    => $if['status'],
                    ];
                    if (count($items) >= $limit) break;
                }
            } else {
                // tanpa device_id -> scan sebagian device (ringan)
                $devices = array_slice($obs->devicesViaHtml(), 0, 10); // batasi 10 device dulu
                foreach ($devices as $d) {
                    $host = $d['hostname'];
                    $ifaces = $obs->interfacesViaHtml($d['device_id']);
                    foreach ($ifaces as $if) {
                        if ($q !== '' && !str_contains(mb_strtolower($if['name']), mb_strtolower($q))) continue;
                        $items[] = [
                            'device_id' => (int)$d['device_id'],
                            'hostname'  => $host,
                            'ifname'    => $if['name'],
                            'port_id'   => $if['id'],
                            'status'    => $if['status'],
                        ];
                        if (count($items) >= $limit) break 2;
                    }
                }
            }

            return response()->json([
                'count' => count($items),
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            Log::error('observium.interfacesJson', ['e'=>$e]);
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
                'count'   => 0,
                'items'   => [],
            ], 500);
        }
    }

    public function graphPng(Request $req, ObserviumClient $obs)
    {
        // default param
        $params = [
            'type'   => $req->get('type', 'port_bits'), // contoh: port_bits
            'id'     => $req->get('port_id'),           // port id
            'from'   => $req->get('from', '-24h'),
            'to'     => $req->get('to', 'now'),
            'width'  => $req->get('width', 600),
            'height' => $req->get('height', 200),
            'legend' => $req->get('legend', 'no'),
        ];

        try {
            $png = $obs->fetchGraphPng($params);
            return response($png, 200)->header('Content-Type', 'image/png');
        } catch (\Throwable $e) {
            return response()->noContent(204);
        }
    }
}
