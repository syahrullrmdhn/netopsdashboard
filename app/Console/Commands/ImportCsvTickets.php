<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Ticket;
use Carbon\Carbon;

class ImportCsvTickets extends Command
{
    protected $signature = 'import:csv-tickets {path : Path to CSV file}';
    protected $description = 'Import tickets (plus chronology) from a CSV export of your Excel file';

    public function handle()
    {
        $path = $this->argument('path');

        if (! file_exists($path) || ! is_readable($path)) {
            $this->error("File not found or unreadable: {$path}");
            return 1;
        }

        // 1) Deteksi delimiter
        $fp    = fopen($path, 'r');
        $first = fgets($fp);
        rewind($fp);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $this->info("Using delimiter '{$delimiter}'");

        // 2) Baca & slugify header
        $headerRow = fgetcsv($fp, 0, $delimiter);
        if (! $headerRow) {
            $this->error("Cannot read header row from {$path}");
            fclose($fp);
            return 1;
        }
        $headers = array_map(fn($h) => Str::slug($h, '_'), $headerRow);
        $this->info('Detected headers: '.implode(',',$headers));

        // 3) Temukan key start & end
        $startKey = null;
        $endKey   = null;
        foreach ($headers as $h) {
            if (!$startKey
             && Str::contains($h, 'start')
             && (Str::contains($h, 'date') || Str::contains($h, 'time'))
            ) {
                $startKey = $h;
            }
            if (!$endKey
             && Str::contains($h, 'end')
             && (Str::contains($h, 'date') || Str::contains($h, 'time'))
            ) {
                $endKey = $h;
            }
        }
        if (! $startKey) {
            $this->error("Header for start-date not found");
            fclose($fp);
            return 1;
        }
        $this->info("Using '{$startKey}' as start-date"
                    .($endKey ? " and '{$endKey}' as end-date":""));

        // 4) Preload customers
        $this->info("Loading customers for fuzzy matching…");
        $customers = Customer::select('id','customer','cid_abh')->get();

        // 5) Proses tiap baris CSV
        $rowIndex = 1;
        while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
            $rowIndex++;
            // pad & sanitize encoding
            $row = array_pad($row, count($headers), '');
            $row = array_map(fn($cell) =>
                mb_convert_encoding($cell, 'UTF-8', 'Windows-1252'),
                $row
            );
            $data = array_combine($headers, $row);

            // 5a) Parse start-date (dukung 'd/m/Y [H:i[:s]]')
            $rawStart = trim($data[$startKey]);
            if ($rawStart === '') {
                $this->warn("Row {$rowIndex}: missing start-date, skipped.");
                continue;
            }
            $start = $this->parseIndoDateTime($rawStart);
            if (! $start) {
                $this->warn("Row {$rowIndex}: invalid start-date “{$rawStart}”, skipped.");
                continue;
            }

            // 5b) Parse end-date (opsional, sama format)
            $end = null;
            if ($endKey && trim($data[$endKey]) !== '') {
                $rawEnd = trim($data[$endKey]);
                if (strtolower($rawEnd) !== '0000-00-00 00:00:00') {
                    $end = $this->parseIndoDateTime($rawEnd);
                    // kalau gagal parse, tetap null
                }
            }

            // 5c) Fuzzy‐match customer
            $cust = null;
            if (! empty($data['cid_abh'] ?? '')) {
                $cust = $customers->firstWhere('cid_abh', $data['cid_abh']);
            }
            if (! $cust && ! empty($data['customer'] ?? '')) {
                $bestPct = 0; $best = null;
                foreach ($customers as $c) {
                    similar_text(
                        Str::lower($data['customer']),
                        Str::lower($c->customer),
                        $pct
                    );
                    if ($pct > $bestPct) {
                        $bestPct = $pct;
                        $best    = $c;
                    }
                }
                if ($bestPct >= 80) {
                    $cust = $best;
                    $this->info("Row {$rowIndex}: fuzzy‐matched “{$data['customer']}” → “{$best->customer}” ({$bestPct}%)");
                }
            }
            if (! $cust) {
                $this->warn("Row {$rowIndex}: no matching customer, skipped.");
                continue;
            }

            // 5d) Buat Ticket
            $ticket = Ticket::create([
                'customer_id'            => $cust->id,
                'open_date'              => $start->toDateString(),
                'start_time'             => $start,
                'end_time'               => $end,
                'issue_type'             => $data['type_of_issue']           ?? null,
                'supplier_ticket_number' => $data['supplier_ticket_number'] ?? null,
                'problem_detail'         => $data['root_cause']             ?? null,
                'action_taken'           => $data['action_taken']           ?? null,
                'preventive_action'      => $data['preventive_action']      ?? null,
                'alert'                  => in_array(strtolower($data['status_rfo_send'] ?? ''), ['yes','true','1']),
                'sla_duration'           => 0,
                'user_id'                => auth()->id() ?? 1,
            ]);

            // 5e) Simpan chronology
            if (! empty($data['chronology'] ?? '')) {
                $ticket->updates()->create([
                    'detail'  => $data['chronology'],
                    'user_id' => auth()->id() ?? 1,
                ]);
            }

            $this->info("Row {$rowIndex}: imported ticket #{$ticket->ticket_number}");
        }

        fclose($fp);
        $this->info("Import complete!");
        return 0;
    }

    /**
     * Parse Indonesian date/time formats: d/m/Y[ H:i[:s]]
     * Returns Carbon instance or null on failure.
     */
    protected function parseIndoDateTime(string $input): ?Carbon
    {
        // normalisasi spasi
        $input = trim(preg_replace('/\s+/', ' ', $input));
        // split date/time
        $parts = explode(' ', $input, 2);
        [$d, $m, $y] = explode('/', $parts[0]) + [null,null,null];
        if (! $d || ! $m || ! $y) {
            return null;
        }
        // pad time
        $time = $parts[1] ?? '00:00:00';
        // tambahkan detik jika perlu
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time .= ':00';
        }
        $iso = sprintf('%04d-%02d-%02d %s', $y, $m, $d, $time);
        try {
            return Carbon::parse($iso);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
