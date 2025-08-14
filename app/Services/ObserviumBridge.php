<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ObserviumBridge
{
    protected string $base;
    protected string $host;
    protected string $user;
    protected string $pass;
    protected bool   $verify;
    protected int    $timeout;

    protected Client   $http;
    protected CookieJar $jar;

    public function __construct()
    {
        $this->base    = rtrim(config('observium.base'), '/');
        $this->user    = config('observium.user');
        $this->pass    = config('observium.pass');
        $this->verify  = (bool) config('observium.verify_ssl', false);
        $this->timeout = (int)  config('observium.timeout', 20);

        $u = parse_url($this->base);
        $this->host = $u['host'] ?? 'localhost';

        $this->jar  = new CookieJar();
        $this->http = new Client([
            'base_uri'        => $this->base . '/',
            'cookies'         => $this->jar,
            'timeout'         => $this->timeout,
            'allow_redirects' => ['max' => 10, 'track_redirects' => true],
            'verify'          => $this->verify,
            'headers'         => [
                'User-Agent'      => 'ND-Bridge/1.1 (+Laravel)',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.8',
                'Host'            => $this->host,           // penting kalau lewat IP
                'Referer'         => $this->base . '/',
            ],
        ]);
    }

    /** Login robust: coba Basic Auth untuk setiap request + fallback form login */
    public function login(): void
    {
        // 1) Hit homepage dengan Basic Auth (kalau webservernya pakai HTTP auth, ini langsung beres)
        $r = $this->http->get('', [
            'http_errors' => false,
            'auth'        => [$this->user, $this->pass],
        ]);
        $html = (string) $r->getBody();
        if ($r->getStatusCode() === 200 && $this->looksLoggedIn($html)) {
            return;
        }

        // 2) Coba form login
        $lr = $this->http->get('login/', [
            'http_errors' => false,
            'auth'        => [$this->user, $this->pass], // tetap kirim; beberapa setup pakai dua lapis
        ]);
        $loginHtml = (string) $lr->getBody();
        $token = $this->extractToken($loginHtml);

        $payload = [
            'username' => $this->user,
            'password' => $this->pass,
            'remember' => '1',
        ];
        if ($token) $payload['token'] = $token;

        $pr = $this->http->post('login/', [
            'http_errors' => false,
            'form_params' => $payload,
        ]);

        // cek lagi ke /
        $cr = $this->http->get('', [
            'http_errors' => false,
            'auth'        => [$this->user, $this->pass],
        ]);
        $html2 = (string) $cr->getBody();

        if (! $this->looksLoggedIn($html2)) {
            Log::warning('Observium login failed', [
                'first_code' => $r->getStatusCode(),
                'form_code'  => $pr->getStatusCode(),
                'final_code' => $cr->getStatusCode(),
                'redirects'  => $cr->getHeader('X-Guzzle-Redirect-History'),
                'cookies'    => $this->jar->toArray(),
            ]);
            throw new \RuntimeException('Tidak bisa login ke Observium (cek BASE host, user/pass, atau blokir IP).');
        }
    }

    protected function looksLoggedIn(string $html): bool
    {
        // heuristik longgar
        return stripos($html, 'Devices') !== false
            || stripos($html, 'Ports') !== false
            || stripos($html, 'Logout') !== false
            || stripos($html, 'Sign out') !== false
            || stripos($html, 'Device List') !== false;
    }

    protected function extractToken(string $html): ?string
    {
        if (preg_match('/name="token"\s+value="([^"]+)"/i', $html, $m)) return $m[1];
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/i', $html, $m)) return $m[1];
        return null;
    }

    public function get(string $path, array $query = []): string
    {
        $this->login();
        $r = $this->http->get(ltrim($path, '/'), [
            'http_errors' => false,
            'query'       => $query,
            'auth'        => [$this->user, $this->pass],
        ]);
        if ($r->getStatusCode() >= 400) {
            throw new \RuntimeException("GET {$path} failed: HTTP ".$r->getStatusCode());
        }
        return (string) $r->getBody();
    }

    public function getBinary(string $path, array $query = []): string
    {
        $this->login();
        $r = $this->http->get(ltrim($path, '/'), [
            'http_errors' => false,
            'query'       => $query,
            'auth'        => [$this->user, $this->pass],
            'headers'     => ['Accept' => '*/*'],
        ]);
        if ($r->getStatusCode() >= 400) {
            throw new \RuntimeException("GET(bin) {$path} failed: HTTP ".$r->getStatusCode());
        }
        return (string) $r->getBody();
    }

    /** ==== PARSER DEVICES (lebih toleran ke variasi tema) ==== */
    public function devices(): array
    {
        $html = $this->get('devices/');
        $c = new Crawler($html);

        $rows = [];

        // Cari semua baris data di tabel devices
        $c->filter('table.table, table.table-striped')->each(function (Crawler $table) use (&$rows) {
            $table->filter('tbody tr')->each(function (Crawler $tr) use (&$rows) {
                $tds = $tr->filter('td');
                if ($tds->count() < 3) return;

                $textAll  = preg_replace('/\s+/', ' ', trim($tr->text('')));
                $status   = (stripos($textAll, 'up') !== false) ? 'up' :
                            ((stripos($textAll, 'down') !== false) ? 'down' : 'unknown');

                // hostname umumnya ada link ke /device/
                $hostname = '';
                $aDev = $tr->filter('a')->reduce(function(Crawler $a) {
                    $href = $a->attr('href') ?? '';
                    return str_contains($href, '/device/') || str_contains($href, 'device=') || str_contains($href, 'dev=');
                })->first();
                if ($aDev->count()) $hostname = trim($aDev->text(''));

                // kolom lain: coba best-effort
                $os       = $tds->eq(2)->text(''); // seringnya OS / platform
                $sysname  = $tds->eq(3)->text('');
                $hardware = $tds->eq(4)->text('');
                $location = $tds->last()->text('');

                $rows[] = [
                    'status'   => strtolower(trim($status)),
                    'hostname' => trim($hostname),
                    'os'       => trim($os),
                    'sysname'  => trim($sysname),
                    'hardware' => trim($hardware),
                    'location' => trim($location),
                ];
            });
        });

        // bersihkan baris kosong
        $rows = array_values(array_filter($rows, fn($r) => $r['hostname'] !== ''));

        $total = count($rows);
        $up    = collect($rows)->where('status','up')->count();
        $down  = collect($rows)->where('status','down')->count();

        return compact('total','up','down','rows');
    }

    /** ==== PARSER PORTS (interfaces) ==== */
    public function ports(?string $q = null): array
    {
        $html = $this->get('ports/');
        $c = new Crawler($html);

        $list = [];
        $c->filter('table.table, table.table-striped')->each(function (Crawler $table) use (&$list) {
            $table->filter('tbody tr')->each(function (Crawler $tr) use (&$list) {
                $tds = $tr->filter('td');
                if ($tds->count() < 2) return;

                $ifLabel  = trim($tds->eq(0)->text(''));
                $ifName   = trim($tds->eq(1)->text(''));

                // hostname: cari link /device/ paling dekat
                $hostname = '';
                $aDev = $tr->filter('a')->reduce(function(Crawler $a) {
                    $href = $a->attr('href') ?? '';
                    return str_contains($href, '/device/') || str_contains($href, 'device=');
                })->first();
                if ($aDev->count()) $hostname = trim($aDev->text(''));

                // port_id dari link /port/<id> atau graph.php?…id=<id>
                $portId = null;
                $tr->filter('a')->each(function (Crawler $a) use (&$portId) {
                    $href = $a->attr('href') ?? '';
                    if (!$href) return;
                    if (preg_match('#/port/(\d+)#', $href, $m))     $portId = (int) $m[1];
                    if (preg_match('/[?&](?:id|port)=(\d+)/', $href, $m)) $portId = (int) $m[1];
                });

                $list[] = [
                    'port_id'  => $portId,
                    'hostname' => $hostname,
                    'ifname'   => $ifName,
                    'label'    => $ifLabel,
                ];
            });
        });

        // filter by q
        if ($q) {
            $qLower = mb_strtolower($q);
            $list = array_values(array_filter($list, function ($r) use ($qLower) {
                return str_contains(mb_strtolower($r['hostname'].' '.$r['ifname'].' '.$r['label']), $qLower);
            }));
        }

        return $list;
    }

    /** Grafik traffic (bits) */
    public function graphPortBits(int $portId, int $fromTs, ?int $toTs = null, int $w = 640, int $h = 180): string
    {
        $query = [
            'type'   => 'port_bits',
            'id'     => $portId,
            'from'   => $fromTs,
            'legend' => 'no',
            'width'  => $w,
            'height' => $h,
        ];
        if ($toTs) $query['to'] = $toTs;

        return $this->getBinary('graph.php', $query);
    }
}
