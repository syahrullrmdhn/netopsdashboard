<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class WaBotService
{
    protected string $base;

    public function __construct()
    {
        // this must include "http://"
        $this->base = config('services.wa_bot.url');
    }

    public function session(): array
    {
        // this will now GET http://localhost:3001/session
        return Http::get("{$this->base}/session")
                   ->throw()     // bubble up errors instead of silent fail
                   ->json();
    }

    public function send(string $to, string $msg): array
    {
        return Http::post("{$this->base}/send", [
            'to'      => $to,
            'message' => $msg,
        ])->throw()->json();
    }
}
