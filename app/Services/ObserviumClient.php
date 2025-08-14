<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;
use RuntimeException;

class ObserviumClient
{
    protected string $base;
    protected string $user;
    protected string $pass;
    protected bool $verify;

    public function __construct()
    {
        $cfg = config('services.observium');
        $this->base   = rtrim((string)$cfg['base'], '/');
        $this->user   = (string)$cfg['username'];
        $this->pass   = (string)$cfg['password'];
        $this->verify = (bool)($cfg['verify_ssl'] ?? true);

        if (!$this->base || !$this->user || !$this->pass) {
            throw new RuntimeException('OBSERVIUM_BASE/USER/PASS belum di-set.');
        }
    }

    /* ========== Session & Login ========== */

    protected function cookie(): string
    {
        return Cache::remember('observium.session.cookie', 60 * 20, function () {
            $seed = Http::withOptions(['verify' => $this->verify])->get($this->base . '/');
            $cookies = $this->extractCookies($seed->header('Set-Cookie'));

            $resp = Http::withOptions(['verify' => $this->verify])
                ->withHeaders(['Cookie' => $cookies])
                ->asForm()
                ->post($this->base . '/login/', [
                    'username' => $this->user,
                    'password' => $this->pass,
                    'remember' => 1,
                    'submit'   => 'Login',
                ]);

            $cookies = $this->mergeCookies($cookies, $this->extractCookies($resp->header('Set-Cookie')));

            if (!$cookies) {
                $resp = Http::withOptions(['verify' => $this->verify])
                    ->asForm()
                    ->post($this->base . '/', [
                        'username' => $this->user,
                        'password' => $this->pass,
                        'remember' => 1,
                        'submit'   => 'Login',
                    ]);
                $cookies = $this->extractCookies($resp->header('Set-Cookie'));
            }

            if (!$cookies) {
                throw new RuntimeException('Gagal login ke Observium (cookie tidak terbentuk).');
            }
            return $cookies;
        });
    }

    protected function extractCookies($setCookieHeader): string
    {
        $arr = is_array($setCookieHeader) ? $setCookieHeader : ($setCookieHeader ? [$setCookieHeader] : []);
        return collect($arr)->map(fn($c) => Str::before($c, ';'))->filter()->implode('; ');
    }

    protected function mergeCookies(string $a, string $b): string
    {
        $parts = collect([$a, $b])->filter()->flatMap(fn($c) => explode('; ', $c));
        $kv = [];
        foreach ($parts as $p) {
            [$k, $v] = array_pad(explode('=', $p, 2), 2, '');
            if ($k !== '') $kv[$k] = $v;
        }
        return collect($kv)->map(fn($v,$k) => "$k=$v")->implode('; ');
    }

    protected function authedGet(string $path, array $query = [])
    {
        $cookie = $this->cookie();

        $res = Http::withOptions(['verify' => $this->verify])
            ->withHeaders(['Cookie' => $cookie])
            ->get($this->base . '/' . ltrim($path, '/'), $query);

        if ($this->looksLikeLogin($res->body()) || in_array($res->status(), [401, 403], true)) {
            Cache::forget('observium.session.cookie');
            $cookie = $this->cookie();
            $res = Http::withOptions(['verify' => $this->verify])
                ->withHeaders(['Cookie' => $cookie])
                ->get($this->base . '/' . ltrim($path, '/'), $query);
        }
        return $res;
    }

    protected function looksLikeLogin(string $html): bool
    {
        $h = strtolower($html);
        return str_contains($h, 'name="username"') || str_contains($h, 'login to observium') || str_contains($h, 'name="password"');
    }

    /* ========== DOM helper ========== */

    protected function xpath(string $html): DOMXPath
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        return new DOMXPath($dom);
    }

    /* ========== DEVICES LIST (HTML scrape) ========== */

    public function devicesViaHtml(): array
    {
        return Cache::remember('observium.devices.list', 30, function () {
            $res = $this->authedGet('/devices/');
            if (!$res->ok()) {
                throw new RuntimeException('Tidak bisa akses /devices/ (HTTP '.$res->status().')');
            }
            $html = $res->body();

            $xp    = $this->xpath($html);
            $links = $xp->query("//a[contains(@href,'device/device=')]");
            $out   = [];

            /** @var \DOMElement $a */
            foreach ($links as $a) {
                $href = $a->getAttribute('href'); // /device/device=123/
                if (!preg_match('~device/device=(\d+)~', $href, $m)) continue;
                $id = (int)$m[1];

                $hostname = trim(html_entity_decode($a->textContent)) ?: "Device #{$id}";
                if (!isset($out[$id])) {
                    $out[$id] = [
                        'device_id' => $id,
                        'hostname'  => $hostname,
                        'status'    => 1,
                        'os'        => '',
                        'sysname'   => '',
                        'hardware'  => '',
                        'location'  => '',
                        'if_count'  => null,
                    ];
                }
            }

            if (!$out && preg_match_all('~<a[^>]+href="[^"]*device/device=(\d+)[^"]*"[^>]*>(.*?)</a>~is', $html, $ms, PREG_SET_ORDER)) {
                foreach ($ms as $m) {
                    $id = (int)$m[1];
                    $name = trim(strip_tags(html_entity_decode($m[2])));
                    $out[$id] = [
                        'device_id' => $id,
                        'hostname'  => $name ?: "Device #{$id}",
                        'status'    => 1,
                        'os'        => '',
                        'sysname'   => '',
                        'hardware'  => '',
                        'location'  => '',
                        'if_count'  => null,
                    ];
                }
            }

            $devices = array_values($out);

            foreach ($devices as &$d) {
                if (preg_match('~device/device='.$d['device_id'].'.{0,300}?(down|unreach)~is', $html)) {
                    $d['status'] = 0;
                }
            }

            usort($devices, fn($a,$b) => strcasecmp($a['hostname'],$b['hostname']));
            return $devices;
        });
    }

    /* ========== DEVICE DETAIL (OS/sysname/hardware/location/if_count) ========== */

    public function deviceViaHtml(int|string $id): array
    {
        return Cache::remember("observium.device.$id", 60, function () use ($id) {
            $res = $this->authedGet("/device/device={$id}/");
            if (!$res->ok()) {
                return ['device_id'=>(int)$id,'hostname'=>"#{$id}",'status'=>0,'os'=>'','sysname'=>'','hardware'=>'','location'=>'','if_count'=>null];
            }

            $html = $res->body();
            $xp   = $this->xpath($html);

            $title    = $xp->query('//h2')->item(0)?->textContent ?? "#{$id}";
            $hostname = trim(html_entity_decode($title));

            $statusTxt = strtolower($xp->query("//*[contains(@class,'label') or contains(@class,'status')]")->item(0)?->textContent ?? '');
            $status    = (str_contains($statusTxt,'down') || str_contains($statusTxt,'unreach')) ? 0 : 1;

            // Try XPath first
            $getCell = function(array $keys) use ($xp) {
                foreach ($keys as $k) {
                    $n = $xp->query("//*[contains(translate(text(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'$k')]/following::td[1]")->item(0);
                    if ($n) return trim(preg_replace('/\s+/', ' ', $n->textContent));
                }
                return '';
            };

            $os       = $getCell(['operating system','os']);
            $hardware = $getCell(['hardware','platform']);
            $sysname  = $getCell(['sysname','system name','hostname']);
            $location = $getCell(['location','site']);

            // Regex fallback
            if ($os === '' && preg_match('~(Operating System|OS)</td>\s*<td[^>]*>(.*?)</td>~is', $html, $m)) $os = trim(strip_tags($m[2]));
            if ($hardware === '' && preg_match('~Hardware</td>\s*<td[^>]*>(.*?)</td>~is', $html, $m)) $hardware = trim(strip_tags($m[1]));
            if ($sysname === '' && preg_match('~SysName</td>\s*<td[^>]*>(.*?)</td>~is', $html, $m)) $sysname = trim(strip_tags($m[1]));
            if ($location === '' && preg_match('~Location</td>\s*<td[^>]*>(.*?)</td>~is', $html, $m)) $location = trim(strip_tags($m[1]));

            // Count interfaces on tab=ports (cheap check by looking for /port/port=)
            $if_count = null;
            if (preg_match_all('~/port/port=\d+~', $html, $m)) {
                $if_count = count(array_unique($m[0]));
            }

            return [
                'device_id'=>(int)$id,
                'hostname'=>$hostname,
                'status'=>$status,
                'os'=>$os,
                'sysname'=>$sysname,
                'hardware'=>$hardware,
                'location'=>$location,
                'if_count'=>$if_count,
            ];
        });
    }

    /* ========== INTERFACES (from tab ports) ========== */

    public function interfacesViaHtml(int|string $deviceId): array
    {
        $res = $this->authedGet("/device/device={$deviceId}/tab=ports/");
        if ($res->status() === 404 || !$res->ok()) {
            $res = $this->authedGet("/device/device={$deviceId}/ports/");
        }
        if (!$res->ok()) return [];

        $html = $res->body();
        $xp   = $this->xpath($html);
        $ports = [];

        $anchors = $xp->query("//a[contains(@href,'/port/port=')]");
        /** @var \DOMElement $a */
        foreach ($anchors as $a) {
            $href = $a->getAttribute('href');
            if (!preg_match('~/port/port=(\d+)~', $href, $m)) continue;
            $pid = (int)$m[1];
            $name = trim($a->textContent) ?: "port-{$pid}";

            $ports[] = [
                'id'     => $pid,
                'name'   => $name,
                'alias'  => '',
                'speed'  => '',
                'status' => (preg_match('~port=' . $pid . '.{0,120}?(down|lowerLayerDown)~is', $html)) ? 'down' : 'up',
            ];
        }

        if (!$ports && preg_match_all('~/port/port=(\d+)[^>]*>(.*?)</a>~is', $html, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $pid = (int)$m[1];
                $name = trim(strip_tags(html_entity_decode($m[2])));
                $ports[] = ['id'=>$pid,'name'=>$name ?: "port-{$pid}",'alias'=>'','speed'=>'','status'=>'up'];
            }
        }

        $ports = collect($ports)->unique('id')->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)->values()->all();
        return $ports;
    }

    /* ========== Proxy graph.php (PNG) ========== */

    public function fetchGraphPng(array $params): string
    {
        $cookie = $this->cookie();

        $res = Http::withOptions(['verify' => $this->verify])
            ->withHeaders(['Cookie' => $cookie])
            ->get($this->base . '/graph.php', $params);

        if (!$res->ok()) {
            throw new RuntimeException('graph.php error '.$res->status());
        }
        return (string)$res->body();
    }
}
