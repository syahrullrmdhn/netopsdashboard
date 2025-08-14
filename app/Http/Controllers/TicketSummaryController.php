<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class TicketSummaryController extends Controller
{
    public function getChronologySummary(Ticket $ticket): JsonResponse
    {
        $updates = $ticket->updates()->with('user')->orderBy('created_at')->get();

        if ($updates->isEmpty()) {
            return response()->json([
                'summary' => 'Tidak ada data kronologi untuk diringkas.',
                'recommendations' => [],
            ]);
        }

        // Susun prompt kronologi
        $prompt  = "Buat ringkasan singkat (maks 4 kalimat) + daftar rekomendasi langkah berikutnya.\n";
        $prompt .= "Fokus: masalah utama, tindakan penting, dan status terakhir.\n\n";
        $prompt .= "--- AWAL KRONOLOGI ---\n";
        foreach ($updates as $u) {
            $prompt .= sprintf(
                "[%s] %s: %s\n",
                $u->created_at->format('Y-m-d H:i'),
                optional($u->user)->name ?? 'Sistem',
                trim($u->detail)
            );
        }
        $prompt .= "--- AKHIR KRONOLOGI ---\n";

        try {
            $base = config('services.wa_bot.url', 'http://127.0.0.1:3001');
            $resp = Http::timeout(15)->post(rtrim($base, '/').'/summarize', [
                'prompt' => $prompt,
            ]);

            if ($resp->failed()) {
                throw new Exception('Summary service error: '.$resp->status());
            }

            $json = $resp->json();

            // Normal path (Node sudah bersih)
            $summary = data_get($json, 'summary');
            $recs    = data_get($json, 'recommendations');

            // Last-resort: jika masih berupa string JSON di dalam string
            if (is_string($summary) && Str::startsWith(trim($summary), '```')) {
                $clean = preg_replace('/^```json\s*/i', '', trim($summary));
                $clean = preg_replace('/^```\s*/i', '', $clean);
                $clean = preg_replace('/```$/', '', $clean);
                $m = [];
                if (preg_match('/\{[\s\S]*\}$/', $clean, $m)) {
                    $clean = $m[0];
                }
                $decoded = json_decode($clean, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $summary = $decoded['summary'] ?? $summary;
                    $recs    = $decoded['recommendations'] ?? $recs;
                }
            }

            if (!is_array($recs)) $recs = [];

            return response()->json([
                'summary' => $summary ?: 'Ringkasan kosong.',
                'recommendations' => $recs,
            ]);
        } catch (Exception $e) {
            report($e);
            return response()->json([
                'error' => true,
                'summary' => 'Gagal terhubung ke layanan AI.',
                'recommendations' => [],
            ], 500);
        }
    }
}
