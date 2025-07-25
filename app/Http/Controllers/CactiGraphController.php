<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;

class CactiGraphController extends Controller
{
    public function index(Request $r)
    {
        $start      = $r->query('start',  Carbon::now()->subDay()->toDateString());
        $end        = $r->query('end',    Carbon::now()->toDateString());
        $search     = $r->query('search', '');
        $treeItemId = $r->query('tree_item_id');

        $trees = Cache::rememberForever('cacti_graph_trees', function() {
            return DB::connection('cacti')
                ->table('graph_tree')
                ->select('id','name')
                ->orderBy('id')
                ->get();
        });

        $raw = Cache::rememberForever('cacti_graph_tree_items_raw', function() {
            return DB::connection('cacti')
                ->table('graph_tree_items')
                ->select(['id as item_id','graph_tree_id','local_graph_id','title','order_key'])
                ->orderBy('order_key')
                ->get();
        });

        $items = [];
        foreach ($raw->groupBy('graph_tree_id') as $tid => $group) {
            $nested = []; $stack = [];
            foreach ($group as $it) {
                $parts = str_split($it->order_key,3);
                $depth = 0;
                foreach ($parts as $seg) {
                    if ((int)$seg > 0) $depth++;
                    else break;
                }
                $it->children = [];
                if ($depth <= 1) {
                    $nested[] = $it;
                } else {
                    $parent = $stack[$depth-1] ?? null;
                    if ($parent) {
                        $parent->children[] = $it;
                    } else {
                        $nested[] = $it;
                    }
                }
                $stack[$depth] = $it;
                foreach (array_keys($stack) as $d) {
                    if ($d > $depth) unset($stack[$d]);
                }
            }
            $items[$tid] = $nested;
        }

        if (! $treeItemId && ! $search) {
            $graphs = collect();
        } else {
            $flat = $raw->keyBy('item_id');
            $graphIds = [];
            if ($treeItemId) {
                $node = $flat[$treeItemId] ?? null;
                abort_unless($node,404,'Tree item not found');
                $collect = function($n) use (&$collect,&$graphIds) {
                    if ($n->local_graph_id > 0) {
                        $graphIds[] = $n->local_graph_id;
                    }
                    foreach ($n->children as $c) {
                        $collect($c);
                    }
                };
                $collect($node);
                $graphIds = array_unique($graphIds);
            }

            $q = DB::connection('cacti')
                ->table('graph_local as gl')
                ->join('host','gl.host_id','=','host.id')
                ->join('graph_templates as gt','gl.graph_template_id','=','gt.id')
                ->select([
                    'gl.id',
                    DB::raw("CONCAT(host.description,' - ',gt.name) as graph_title")
                ]);

            if ($treeItemId) {
                if (empty($graphIds)) {
                    $graphs = collect();
                } else {
                    $q->whereIn('gl.id', $graphIds);
                }
            }
            if ($search) {
                $q->whereRaw("CONCAT(host.description,' - ',gt.name) LIKE ?", ["%{$search}%"]);
            }
            $graphs = $q->orderBy('gl.id','desc')
                        ->paginate(50)
                        ->appends($r->query());
        }

        return view('cacti.graphs.index', compact(
            'trees','items','graphs','start','end','search','treeItemId'
        ));
    }

    public function image($id, Request $r)
    {
        $start    = $r->query('start');
        $end      = $r->query('end');
        $cactiUrl = rtrim(env('CACTI_WEB_URL'), '/');

        $jar = new CookieJar();
        $client = new Client([
            'base_uri'=>$cactiUrl,
            'cookies' =>$jar,
            'timeout' =>15,
            'verify'  =>false,
            'headers' =>['User-Agent'=>'Mozilla/5.0'],
        ]);

        try {
            $login = $client->post('/index.php', [
                'form_params'=>[
                    'login_username'=>env('CACTI_WEB_USER'),
                    'login_password'=>env('CACTI_WEB_PASS'),
                    'action'=>'login','realm'=>'local',
                ]
            ]);
            if (str_contains((string)$login->getBody(),'login_username')) {
                Log::error('Cacti login failed.');
                return $this->_errorImage("DEBUG: Login failed.");
            }

            $resp = $client->get('/graph_image.php', [
                'query'=>[
                    'local_graph_id'=>$id,
                    'rra_id'=>0,
                    'view_type'=>'tree',
                    'graph_start'=>$start,
                    'graph_end'=>$end,
                ],
            ]);
            $ct = $resp->getHeaderLine('Content-Type');
            if (! str_starts_with($ct,'image/')) {
                Log::error('Unexpected response', ['ct'=>$ct]);
                return $this->_errorImage("DEBUG: Expected image/*, got {$ct}");
            }
            return response($resp->getBody()->getContents(),200)->header('Content-Type',$ct);

        } catch (ConnectException $e) {
            Log::error('Connection error: '.$e->getMessage());
            return $this->_errorImage("DEBUG: Connection Error.\n".$e->getMessage());
        } catch (Exception $e) {
            Log::error('Exception: '.$e->getMessage());
            return $this->_errorImage("DEBUG: Exception.\n".$e->getMessage());
        }
    }

    public function export($id, Request $r)
    {
        $start = $r->query('start');
        $end   = $r->query('end');
        $cactiUrl = rtrim(env('CACTI_WEB_URL'), '/');

        $jar = new CookieJar();
        $client = new Client([
            'base_uri'=>$cactiUrl,
            'cookies' =>$jar,
            'timeout' =>15,
            'verify'  =>false,
            'headers' =>['User-Agent'=>'Mozilla/5.0'],
        ]);

        try {
            $login = $client->post('/index.php', [
                'form_params'=>[
                    'login_username'=>env('CACTI_WEB_USER'),
                    'login_password'=>env('CACTI_WEB_PASS'),
                    'action'=>'login','realm'=>'local',
                ]
            ]);
            if (str_contains((string)$login->getBody(),'login_username')) {
                abort(500,'Cacti authentication failed.');
            }

            $resp = $client->get('/graph_xport.php', [
                'query'=>[
                    'local_graph_id'=>$id,
                    'rra_id'=>0,
                    'graph_start'=>$start,
                    'graph_end'=>$end
                ],
            ]);
            $body = $resp->getBody()->getContents();
            return response($body,200)
                   ->header('Content-Type','text/csv')
                   ->header('Content-Disposition','attachment; filename="cacti_graph_'.$id.'.csv"');

        } catch (Exception $e) {
            abort(500,'CSV export failed: '.$e->getMessage());
        }
    }

    public function show($id, Request $r)
    {
        $startInput = $r->query('start', Carbon::now()->subDay()->format('Y-m-d\TH:i'));
        $endInput   = $r->query('end',   Carbon::now()->format('Y-m-d\TH:i'));

        try {
            $startDT = Carbon::parse($startInput);
        } catch (Exception $e) {
            $startDT = Carbon::now()->subDay();
            $startInput = $startDT->format('Y-m-d\TH:i');
        }
        try {
            $endDT = Carbon::parse($endInput);
        } catch (Exception $e) {
            $endDT   = Carbon::now();
            $endInput = $endDT->format('Y-m-d\TH:i');
        }
        $startTs = $startDT->timestamp;
        $endTs   = $endDT->timestamp;

        $graph = DB::connection('cacti')
            ->table('graph_local as gl')
            ->join('host','gl.host_id','=','host.id')
            ->leftJoin('graph_tree_items as gti', function($j) {
                $j->on('gti.local_graph_id','=', 'gl.id')
                  ->where('gti.rra_id', 0);
            })
            ->select([
                'gl.id',
                'gl.host_id',
                'host.description   as host_description',
                'gti.title          as interface_title',
            ])
            ->where('gl.id',$id)
            ->first();

        abort_unless($graph,404,'Graph not found');

        return view('cacti.graphs.show', compact(
            'graph','startInput','endInput','startTs','endTs'
        ));
    }

    private function _errorImage(string $text, int $w = 400, int $h = 200)
    {
        if (! function_exists('imagecreate')) {
            return response("GD required",500)->header('Content-Type','text/plain');
        }
        $img = imagecreatetruecolor($w,$h);
        $bg  = imagecolorallocate($img,254,235,235);
        $brd = imagecolorallocate($img,220, 53, 69);
        $txt = imagecolorallocate($img,50, 50, 50);
        imagefill($img,0,0,$bg);
        imagerectangle($img,0,0,$w-1,$h-1,$brd);
        $y=15;
        foreach (explode("\n",wordwrap($text,(int)($w/8),"\n")) as $line) {
            $x=(int)(($w-strlen($line)*imagefontwidth(3))/2);
            imagestring($img,3,max(5,$x),$y,$line,$txt);
            $y+=15;
        }
        ob_start(); imagepng($img);
        $data=ob_get_clean(); imagedestroy($img);
        return response($data,200)->header('Content-Type','image/png');
    }
}
