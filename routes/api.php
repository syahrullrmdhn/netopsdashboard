<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\NocController;
use App\Http\Controllers\EscalationController;
use App\Http\Controllers\ReportController;
use App\Models\CustomerGroup;
use App\Http\Controllers\Api\GroupController;

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
Route::get('/api/rfo/{id}/pdf', [TicketController::class, 'rfoPdf']);
// ─── NOC / Shift Endpoints ───────────────────────────────────────────────────
// Who is on‑duty right now & who’s next
Route::get('noc/onduty', [NocController::class, 'apiOnDuty'])
     ->name('api.noc.onduty');

// Handover history for today or an optional date (YYYY‑MM‑DD)
Route::get('noc/history/{date?}', [NocController::class, 'apiHistory'])
     ->where('date', '\d{4}-\d{2}-\d{2}')
     ->name('api.noc.history');
Route::get('escalation‐levels/{level}', [EscalationLevelController::class,'show']);
Route::post('tickets/open-wabot', [TicketController::class, 'openViaWabot']);
Route::get('/customer-groups/search', function(Request $request) {
    $keyword = $request->query('q', '');
    $groups = CustomerGroup::query()
        ->when($keyword, function($q) use ($keyword) {
            $q->where('group_name', 'like', '%'.$keyword.'%');
        })
        ->withCount('customers')
        ->orderBy('group_name')
        ->get(['id','group_name']);

    return response()->json($groups->map(function($g){
        return [
            'id' => $g->id,
            'name' => $g->group_name,
            'customer_count' => $g->customers_count,
        ];
    }));
});
Route::get('groups', [GroupController::class, 'index']);
