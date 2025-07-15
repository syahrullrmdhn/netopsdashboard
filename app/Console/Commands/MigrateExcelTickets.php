<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use App\Models\Customer;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MigrateExcelTickets extends Command
{
    /**
     * Nama dan signature dari command.
     *
     * @var string
     */
    protected $signature = 'migrate:tickets {file : Path to the Excel file (.xlsx or .xls)}';

    /**
     * Deskripsi singkat.
     *
     * @var string
     */
    protected $description = 'Migrate legacy tickets from an Excel file into the tickets table';

    public function handle()
    {
        $arg = $this->argument('file');

        // 1) Cari file di beberapa lokasi
        $candidates = [
            $arg,
            base_path($arg),
            public_path($arg),
            storage_path($arg),
        ];
        $filePath = null;
        foreach ($candidates as $path) {
            if (file_exists($path) && ! is_dir($path)) {
                $filePath = $path;
                break;
            }
        }
        if (! $filePath) {
            $this->error("File not found or is a directory: {$arg}");
            return 1;
        }

        // 2) Pilih reader berdasarkan ekstensi
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'xlsx') {
            $reader = ReaderEntityFactory::createXLSXReader();
        } elseif ($ext === 'xls') {
            $reader = ReaderEntityFactory::createXLSReader();
        } else {
            $this->error("Unsupported file type: .{$ext}");
            return 1;
        }

        $reader->open($filePath);
        $this->info("Opened file: {$filePath}");

        // 3) Preload semua customer untuk fuzzy‐matching
        $customers = Customer::select('id','customer','cid_abh')->get();

        $rowCount = 0;
        $created  = 0;
        $skipped  = 0;
        $headers  = [];

        // 4) Loop per sheet & per row
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowCount++;
                $cells = $row->toArray();

                // baris pertama → header
                if ($rowCount === 1) {
                    $headers = array_map(fn($h)=>Str::slug($h,'_'), $cells);
                    $this->info('Detected headers: '.implode(',',$headers));
                    continue;
                }

                // 5) Map header → data
                $data = [];
                foreach ($headers as $i => $col) {
                    $val = $cells[$i] ?? null;
                    if ($val instanceof \DateTimeInterface) {
                        $val = $val->format('Y-m-d H:i:s');
                    }
                    $data[$col] = trim((string)($val ?? ''));
                }

                // 6) Cari kolom tanggal mulai
                $dateKey = null;
                foreach (['date_start_time','open_date','start_date'] as $cand) {
                    if (array_key_exists($cand, $data)) {
                        $dateKey = $cand;
                        break;
                    }
                }
                if (! $dateKey || $data[$dateKey] === '') {
                    $this->warn("Row {$rowCount}: missing start-date, skipped.");
                    $skipped++;
                    continue;
                }

                // 7) Parse tanggal mulai
                try {
                    $start = Carbon::parse($data[$dateKey]);
                } catch (\Throwable $e) {
                    $this->warn("Row {$rowCount}: invalid start-date “{$data[$dateKey]}”, skipped.");
                    $skipped++;
                    continue;
                }

                // 8) Parse tanggal selesai (opsional)
                $end = null;
                if (! empty($data['date_end_time'])) {
                    try {
                        $end = Carbon::parse($data['date_end_time']);
                    } catch (\Throwable $e) {
                        // abaikan jika gagal
                    }
                }

                // 9) Fuzzy‐match customer
                $cid  = $data['cid_abh']  ?? '';
                $name = $data['customer'] ?? '';
                $cust = null;

                // exact cid
                if ($cid) {
                    $cust = $customers->firstWhere('cid_abh', $cid);
                }
                // nama ≥80%
                if (! $cust) {
                    $bestPct = 0; $best = null;
                    foreach ($customers as $c) {
                        similar_text(
                            mb_strtolower($name),
                            mb_strtolower($c->customer),
                            $pct
                        );
                        if ($pct > $bestPct) {
                            $bestPct = $pct;
                            $best    = $c;
                        }
                    }
                    if ($bestPct >= 80) {
                        $cust = $best;
                        $this->info("Row {$rowCount}: fuzzy‐matched name “{$name}”→“{$best->customer}” ({$bestPct}%)");
                    }
                }
                if (! $cust) {
                    $this->warn("Row {$rowCount}: no matching customer for cid='{$cid}', name='{$name}', skipped.");
                    $skipped++;
                    continue;
                }

                // 10) Insert ke tickets
                Ticket::create([
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
                ]);

                $created++;
            }
        }

        $reader->close();

        $this->info("Done: {$created} tickets created, {$skipped} rows skipped.");
        return 0;
    }
}
