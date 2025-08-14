<?php

namespace App\Http\Controllers;

use App\Services\ObserviumClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NmsController extends Controller
{
public function index(ObserviumClient $obs)
{
    $devices = [];
    $stats   = ['total'=>0,'up'=>0,'down'=>0];
    $err     = null;

    try {
        $devices = $obs->devicesViaHtml();
        $stats['total'] = count($devices);
        $stats['up']    = collect($devices)->where('status', 1)->count();
        $stats['down']  = $stats['total'] - $stats['up'];
    } catch (\Throwable $e) {
        report($e);
        $err = $e->getMessage(); // ← biar keliatan di UI
    }

    return view('nms.index', compact('devices','stats','err'));
}

    public function deviceInterfaces(int $deviceId, ObserviumClient $obs)
    {
        try {
            $rows = Cache::remember("observium.device.$deviceId.ports", 180, fn() => $obs->interfacesViaHtml($deviceId));
            return response()->json(['interfaces' => $rows]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error'=>true,'message'=>$e->getMessage()], 500);
        }
    }

    public function interfacesAll(ObserviumClient $obs)
    {
        try {
            $data = Cache::remember('observium.all.interfaces.v2', 180, function () use ($obs) {
                $out = [];
                $devices = $obs->devicesViaHtml();
                foreach ($devices as $d) {
                    $ports = $obs->interfacesViaHtml($d['device_id']);
                    foreach ($ports as $p) {
                        $out[] = array_merge($d, [
                            'port_id' => $p['id'],
                            'ifName'  => $p['name'],
                            'alias'   => $p['alias'],
                            'speed'   => $p['speed'],
                            'ifStatus'=> $p['status'],
                        ]);
                    }
                }
                return $out;
            });
            return response()->json(['interfaces' => $data]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error'=>true,'message'=>$e->getMessage()], 500);
        }
    }

    /** Suggest interface berdasarkan query */
    public function interfacesSearch(Request $req, ObserviumClient $obs)
    {
        $q = trim($req->query('q', ''));
        if ($q === '') return response()->json(['items' => []]);

        // ambil dari cache all interfaces saja agar cepat
        $all = Cache::remember('observium.all.interfaces.v2', 180, function () use ($obs) {
            $out = [];
            $devices = $obs->devicesViaHtml();
            foreach ($devices as $d) {
                $ports = $obs->interfacesViaHtml($d['device_id']);
                foreach ($ports as $p) {
                    $out[] = array_merge($d, [
                        'port_id' => $p['id'],
                        'ifName'  => $p['name'],
                        'alias'   => $p['alias'],
                        'speed'   => $p['speed'],
                        'ifStatus'=> $p['status'],
                    ]);
                }
            }
            return $out;
        });

        $qLower = mb_strtolower($q);
        $score = function ($r) use ($qLower) {
            $hay = mb_strtolower(($r['hostname'] ?? '') . ' ' . ($r['ifName'] ?? '') . ' ' . ($r['alias'] ?? '') . ' ' . ($r['location'] ?? ''));
            $s = 0;
            if (str_contains($hay, $qLower)) $s += 10;
            if (mb_strtolower($r['ifName'] ?? '') === $qLower) $s += 5;
            if (mb_strtolower($r['hostname'] ?? '') === $qLower) $s += 3;
            return $s;
        };

        $filtered = array_values(array_filter($all, function ($r) use ($qLower) {
            return str_contains(mb_strtolower($r['hostname'] ?? ''), $qLower) ||
                   str_contains(mb_strtolower($r['ifName'] ?? ''), $qLower)   ||
                   str_contains(mb_strtolower($r['alias'] ?? ''), $qLower)    ||
                   str_contains(mb_strtolower($r['location'] ?? ''), $qLower);
        }));

        usort($filtered, fn($a,$b) => $score($b) <=> $score($a));
        $top = array_slice($filtered, 0, 12);

        return response()->json(['items' => $top]);
    }

    public function graph(Request $req, ObserviumClient $obs)
    {
        $params = $req->all();
        $params['type']   = $params['type']   ?? 'port_bits';
        $params['legend'] = $params['legend'] ?? 'no';
        $params['width']  = $params['width']  ?? 1000;
        $params['height'] = $params['height'] ?? 300;
        $params['bg']     = $params['bg']     ?? 'FFFFFF00';

        if ($preset = $req->get('range')) {
            $to = time();
            $map = ['1h'=>3600,'6h'=>21600,'24h'=>86400,'7d'=>604800,'30d'=>2592000];
            $sec = $map[$preset] ?? 86400;
            $params['to']   = $to;
            $params['from'] = $to - $sec;
        } else {
            $params['to']   = $params['to']   ?? time();
            $params['from'] = $params['from'] ?? ($params['to'] - 86400);
        }

        try {
            $png = $obs->fetchGraphPng($params);
            return response($png, 200)->header('Content-Type', 'image/png');
        } catch (\Throwable $e) {
            report($e);
            return response('Graph error', 500);
        }
    }
}
