<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class ObserviumScraper
{
    protected string $base;
    protected string $user;
    protected string $pass;
    protected bool   $verify;

    public function __construct()
    {
        $cfg = config('observium');
        $this->base   = $cfg['base'];
        $this->user   = $cfg['user'];
        $this->pass   = $cfg['pass'];
        $this->verify = (bool) $cfg['verify'];
    }

    /** GET helper (Basic Auth) */
    protected function get(string $path, array $query = []): string
    {
        $url = $this->base . '/' . ltrim($path, '/');
        $res = Http::withOptions([
                    'verify'          => $this->verify,
                    'allow_redirects' => true,
                    'timeout'         => 30,
                ])
                ->withBasicAuth($this->user, $this->pass)
                ->get($url, $query);

        if (!$res->ok()) {
            throw new \RuntimeException("Observium GET {$path} failed: {$res->status()}");
        }
        return (string) $res->body();
    }

    /** Load HTML → DOMXPath (tanpa symfony) */
    protected function xpath(string $html): DOMXPath
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        return new DOMXPath($dom);
    }

    protected function nodeText(?\DOMNode $node): string
    {
        return $node ? trim(preg_replace('/\s+/', ' ', $node->textContent)) : '';
    }

    /*** DEVICES ***/
    public function devices(?string $q = null): array
    {
        // Halaman devices utama
        $html = $this->get('/devices/');
        $xp   = $this->xpath($html);

        // Baris perangkat tipikal: baris yang memiliki link /device/device=
        $rows = $xp->query("//table//tr[.//a[contains(@href,'/device/device=')]]");

        $items = [];
        $up = 0; $down = 0;

        foreach ($rows as $tr) {
            /** hostname & sysname */
            $aDev = $xp->query(".//a[contains(@href,'/device/device=')]", $tr)->item(0);
            $hostname = $this->nodeText($aDev);
            if ($q && stripos($hostname, $q) === false) {
                // jika pakai filter pencarian sederhana
                continue;
            }

            /** status heuristik */
            $status = 'unknown';
            if ($xp->query(".//*[contains(@class,'up') or contains(@class,'success')]", $tr)->length) {
                $status = 'up'; $up++;
            } elseif ($xp->query(".//*[contains(@class,'down') or contains(@class,'danger')]", $tr)->length) {
                $status = 'down'; $down++;
            }

            /** kolom lain (heuristik berdasarkan posisi) */
            $tds = $xp->query(".//td", $tr);
            // Observium umumnya: [status] [hostname] [os/hw] [location] [ports] ...
            $osHw    = $this->nodeText($tds->item(2) ?? null);
            $loc     = $this->nodeText($tds->item(4) ?? null);
            $portsTd = $this->nodeText($tds->item(5) ?? null);

            // coba ekstrak bagian 'OS' dan 'Hardware' jika digabung
            $os = ''; $hw = '';
            if ($osHw) {
                if (preg_match('/OS:\s*([^|]+)\|?/i', $osHw, $m)) $os = trim($m[1]);
                if (preg_match('/Hardware:\s*([^|]+)\|?/i', $osHw, $m)) $hw = trim($m[1]);
                if (!$os && !$hw) $os = $osHw; // fallback
            }

            $items[] = [
                'status'    => $status,
                'hostname'  => $hostname,
                'os'        => $os,
                'sysname'   => '',       // tidak selalu tersedia di list, bisa diambil di detail kalau mau
                'hardware'  => $hw,
                'location'  => $loc,
                'interfaces'=> $portsTd ?: null,
            ];
        }

        // Jika up/down belum terbaca, hitung dari items
        if (($up + $down) === 0) {
            foreach ($items as $it) {
                if ($it['status'] === 'up') $up++;
                if ($it['status'] === 'down') $down++;
            }
        }

        return [
            'total'   => count($items),
            'up'      => $up,
            'down'    => $down,
            'devices' => $items,
        ];
    }

    /*** INTERFACES (ports) ***/
    public function interfaces(?string $q = null, int $limit = 50): array
    {
        // Halaman ports global
        // /ports/ sering cukup. Jika install berbeda, bisa gunakan /ports/all/ atau /search/ports/
        $html = $this->get('/ports/');
        $xp   = $this->xpath($html);

        $rows = $xp->query("//table//tr[.//a[contains(@href,'/port/')]]");

        $items = [];
        foreach ($rows as $tr) {
            $aPort = $xp->query(".//a[contains(@href,'/port/')]", $tr)->item(0);
            if (!$aPort) continue;

            $href = $aPort->getAttribute('href'); // e.g. /port/123/
            $ifname = $this->nodeText($aPort);

            // hostname dari baris yang sama (ada link device)
            $aHost = $xp->query(".//a[contains(@href,'/device/device=')]", $tr)->item(0);
            $hostname = $this->nodeText($aHost);

            // deskripsi interface biasanya ada di kolom lain
            $tds = $xp->query(".//td", $tr);
            $descr = $this->nodeText($tds->item(2) ?? null);

            if ($q) {
                $hay = $hostname . ' ' . $ifname . ' ' . $descr;
                if (stripos($hay, $q) === false) continue;
            }

            // Ambil port id dari href
            $portId = null;
            if (preg_match('#/port/(\d+)#', $href, $m)) $portId = (int)$m[1];

            $items[] = [
                'hostname' => $hostname,
                'ifname'   => $ifname,
                'descr'    => $descr,
                'port_id'  => $portId,
                'graph'    => $portId ? route('monitoring.graph.port', ['id'=>$portId, 'from'=>request('from','-1d'), 'to'=>request('to','now')]) : null,
            ];

            if (count($items) >= $limit) break;
        }

        return [
            'count' => count($items),
            'items' => $items,
        ];
    }

    /** Proxy gambar grafik port bits dari Observium */
    public function portGraphPng(int $portId, string $from = '-1d', string $to = 'now', int $w = 1000, int $h = 300): string
    {
        // Observium graph.php typical params:
        // type=port_bits, id=<portId>, from, to, width, height, legend=no
        $img = $this->get('/graph.php', [
            'type'   => 'port_bits',
            'id'     => $portId,
            'from'   => $from,
            'to'     => $to,
            'width'  => $w,
            'height' => $h,
            'legend' => 'no',
        ]);

        return $img; // binary png
    }
}
