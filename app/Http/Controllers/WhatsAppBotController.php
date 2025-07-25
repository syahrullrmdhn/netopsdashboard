<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\WaBotService;

class WhatsappBotController extends Controller
{
    public function index() {
        return view('whatsapp-bot.index');
    }

public function session(WaBotService $bot)
{
    try {
        $data = $bot->session();       // e.g. ['connected'=>bool,'qr'=>string|null]
    } catch (\Throwable $e) {
        // if Node service is down or unreachable, still return valid JSON
        $data = [
            'connected' => false,
            'qr'        => null,
        ];
        // optionally log: \Log::error($e);
    }

    return response()->json($data);
}


    public function send(Request $req, WaBotService $bot) {
        $req->validate(['to'=>'required','message'=>'required']);
        return response()->json($bot->send($req->to, $req->message));
    }
}
