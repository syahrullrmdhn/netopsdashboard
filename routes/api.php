<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TicketController;
use App\Http\Controllers\NocController;
use App\Http\Controllers\EscalationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketSummaryController;
use App\Http\Controllers\Api\GroupController;

use App\Models\CustomerGroup;
use App\Models\EscalationLevel;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Semua route di file ini otomatis diprefix /api
| (contoh: Route::get('foo') => /api/foo)
*/

// ── Reports ────────────────────────────────────────────────────────────────
Route::get('reports/export-pdf',   [ReportController::class, 'exportPdf'])->name('reports.exportPdf');
Route::get('reports/print',        [ReportController::class, 'printPreview'])->name('reports.print');
Route::get('reports/export-spout', [ReportController::class, 'exportSpout'])->name('reports.exportSpout');

// (opsional) user SPA pakai sanctum
Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());

// ── Tickets ────────────────────────────────────────────────────────────────
// Daftar tiket open
Route::get('tickets/open', [TicketController::class, 'apiOpenTickets'])->name('api.tickets.open');

// Ambil tiket via nomor tiket
Route::get('tickets/number/{ticket_number}', [TicketController::class, 'apiShowByNumber'])->name('api.tickets.byNumber');

// Generate RFO PDF (HATI-HATI: jangan tulis '/api/...', cukup 'rfo/...') → hasilnya /api/rfo/{id}/pdf
Route::get('rfo/{id}/pdf', [TicketController::class, 'rfoPdf'])->name('api.rfo.pdf');

// Ringkasan kronologi (dipanggil modal summary)
Route::get('tickets/{ticket}/chronology-summary', [TicketSummaryController::class, 'getChronologySummary'])
    ->name('tickets.chronology.summary');

// Buat tiket via WhatsApp bot
Route::post('tickets/open-wabot', [TicketController::class, 'openViaWabot'])->name('api.tickets.openWabot');

// ── NOC / Shift ────────────────────────────────────────────────────────────
Route::get('noc/onduty', [NocController::class, 'apiOnDuty'])->name('api.noc.onduty');

Route::get('noc/history/{date?}', [NocController::class, 'apiHistory'])
    ->where('date', '\d{4}-\d{2}-\d{2}')
    ->name('api.noc.history');

// ── Escalation (diambil oleh Node bot) ─────────────────────────────────────
// WARNING: pakai hyphen biasa '-' (BUKAN karakter 2010) → 'escalation-levels'
Route::get('escalation-levels/{level}', function ($level) {
    $lvl = EscalationLevel::where('level', $level)->firstOrFail();
    return response()->json([
        'level' => $lvl->level,
        'label' => $lvl->label,
        'name'  => $lvl->name,
        'phone' => $lvl->phone,
        'email' => $lvl->email,
    ]);
})->name('api.escalation.level');

// ── Groups ─────────────────────────────────────────────────────────────────
Route::get('groups', [GroupController::class, 'index'])->name('api.groups.index');

// Pencarian customer groups (opsional end-point yang sudah ada di filemu)
Route::get('customer-groups/search', function (Request $request) {
    $keyword = $request->query('q', '');
    $groups = CustomerGroup::query()
        ->when($keyword, fn ($q) => $q->where('group_name', 'like', '%'.$keyword.'%'))
        ->withCount('customers')
        ->orderBy('group_name')
        ->get(['id','group_name']);

    return response()->json($groups->map(fn ($g) => [
        'id'             => $g->id,
        'name'           => $g->group_name,
        'customer_count' => $g->customers_count,
    ]));
})->name('api.customerGroups.search');
