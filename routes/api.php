<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\NocController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::get('reports/export-pdf',   [ReportController::class,'exportPdf'])->name('reports.exportPdf');
Route::get('reports/print',        [ReportController::class,'printPreview'])->name('reports.print');
Route::get('reports/export-spout', [ReportController::class,'exportSpout'])->name('reports.exportSpout');
// If you use Sanctum for SPA auth, this returns the authenticated user:
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ─── Ticket Endpoints ────────────────────────────────────────────────────────
// List all currently-open tickets
Route::get('tickets/open', [TicketController::class, 'apiOpenTickets'])
     ->name('api.tickets.open');

// Lookup one ticket by its ticket_number
Route::get('tickets/number/{ticket_number}', [TicketController::class, 'apiShowByNumber'])
     ->name('api.tickets.byNumber');

// ─── NOC / Shift Endpoints ───────────────────────────────────────────────────
// Who is on‑duty right now & who’s next
Route::get('noc/onduty', [NocController::class, 'apiOnDuty'])
     ->name('api.noc.onduty');

// Handover history for today or an optional date (YYYY‑MM‑DD)
Route::get('noc/history/{date?}', [NocController::class, 'apiHistory'])
     ->where('date', '\d{4}-\d{2}-\d{2}')
     ->name('api.noc.history');
