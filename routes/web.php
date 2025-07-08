<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NocController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SLAController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketUpdateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EscalationController;
use App\Http\Controllers\EmailSettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/* 1) Alihkan root ke halaman login */
Route::redirect('/', '/login');

/* 2) Auth routes publik */
Route::get ('/login',    [AuthenticatedSessionController::class,'create'])->name('login');
Route::post('/login',    [AuthenticatedSessionController::class,'store']);
Route::get ('/register', [RegisteredUserController::class,'create'])->name('register');
Route::post('/register', [RegisteredUserController::class,'store']);

/* 3) Rute dilindungi */
Route::middleware('auth')->group(function () {

    /* ---------- Logout ---------- */
    Route::post('/logout', [AuthenticatedSessionController::class,'destroy'])
         ->name('logout');

    /* ---------- Dashboard ---------- */
    Route::get('/dashboard', [DashboardController::class,'index'])
         ->name('dashboard');

    /* ===============================================================
     |  TICKETS — Import & Export HARUS DIDEFINISIKAN DULU
     *===============================================================*/
    Route::post('/tickets/import', [TicketController::class,'import'])
         ->name('tickets.import');

    Route::get('/tickets/export-template', [TicketController::class,'exportTemplate'])
         ->name('tickets.export.template');

    Route::get('/tickets/export', [TicketController::class,'exportTickets'])
         ->name('tickets.export');

    /* ---------- Tickets CRUD & child routes ---------- */
    Route::patch('tickets/{ticket}/close', [TicketController::class,'close'])
         ->name('tickets.close');

    Route::post('tickets/{ticket}/updates', [TicketUpdateController::class,'store'])
         ->name('tickets.updates.store');

    Route::resource('tickets', TicketController::class);

    /* ---------- RFO ---------- */
    Route::get ('tickets/{ticket}/rfo',     [TicketController::class,'rfo'])
         ->name('tickets.rfo');
    Route::get ('tickets/{ticket}/rfo/pdf', [TicketController::class,'rfoPdf'])
         ->name('tickets.rfo.pdf');
    Route::post('tickets/{ticket}/rfo/pdf', [TicketController::class,'rfoPdf']); // same action (POST)
    // RFO preview & PDF
    Route::match(['get','post'],'tickets/{ticket}/rfo/pdf', [TicketController::class,'rfoPdf'])->name('tickets.rfo.pdf');
    /* ===============================================================
     |  CUSTOMERS
     *===============================================================*/
    Route::get('customers/json',    [CustomerController::class,'json'])->name('customers.json');
    Route::get('customers-export',  [CustomerController::class,'export'])->name('customers.export');
    Route::resource('customers', CustomerController::class);

    /* ===============================================================
     |  REPORTS
     *===============================================================*/
    Route::get('reports',               [ReportController::class,'index'])->name('reports.index');
    Route::get('reports/export-pdf',    [ReportController::class,'exportPdf'])->name('reports.exportPdf');
    Route::get('reports/print',         [ReportController::class,'printPreview'])->name('reports.print');
    Route::get('reports/export-spout',  [ReportController::class,'exportSpout'])->name('reports.exportSpout');

    /* ===============================================================
     |  SLA
     *===============================================================*/
    Route::get('sla',                     [SLAController::class,'index'])->name('sla.index');
    Route::get('sla/device/{device}',     [SLAController::class,'device'])->name('sla.device');
    Route::get('sla/{sensorId}',          [SLAController::class,'show'])->whereNumber('sensorId')->name('sla.show');
    Route::get('sla/{sensorId}/download-pdf', [SLAController::class,'downloadPdf'])->name('sla.downloadPdf');

    /* ===============================================================
     |  PERFORMANCE
     *===============================================================*/
    Route::get('/performance',               [PerformanceController::class,'index'])->name('performance.index');
    Route::get('/performance/eval',          [PerformanceController::class,'evalDashboard'])->name('performance.eval');
    Route::get('/performance/detail/{type}', [PerformanceController::class,'detail'])->name('performance.detail');

    /* ===============================================================
     |  USER & ROLE MANAGEMENT (with policy)
     *===============================================================*/
    Route::middleware('can:manage users')->group(function () {
        Route::post('users/{user}/reset-password', [UserController::class,'resetPassword'])
             ->name('users.resetPassword');

        Route::resource('users', UserController::class);

        Route::get ('users/{user}/roles', [RoleController::class,'assignRoleForm'])
             ->name('users.roles.edit');
        Route::post('users/{user}/roles', [RoleController::class,'assignRole'])
             ->name('users.roles.update');

        Route::resource('roles', RoleController::class);
    });

    /* ===============================================================
     |  NOC (Network Operations Center)
     *===============================================================*/
    Route::prefix('noc')->name('noc.')->group(function () {
        Route::get ('manage-shifts',   [NocController::class,'manageShifts'])->name('manageShifts');
        Route::post('update-shifts',   [NocController::class,'updateShifts'])->name('updateShifts');
        Route::get ('handover',        [NocController::class,'handover'])->name('handover');
        Route::post('store-handover',  [NocController::class,'storeHandover'])->name('storeHandover');
        Route::get ('history',         [NocController::class,'history'])->name('history');
    });

    /* ===============================================================
     |  ESCALATIONS & SETTINGS
     *===============================================================*/
    Route::get ('/escalations', [EscalationController::class,'index'])
         ->name('escalations.index')
         ->middleware('can:manage escalation');

    Route::post('/escalations', [EscalationController::class,'store'])
         ->name('escalations.store')
         ->middleware('can:manage escalation');

    Route::post('tickets/{ticket}/escalate', [EscalationController::class,'send'])
         ->name('tickets.escalate')
         ->middleware('can:manage escalation');

    Route::middleware('can:manage settings')->group(function () {
        Route::get ('settings/mail', [EmailSettingsController::class,'edit'])->name('settings.mail.edit');
        Route::post('settings/mail', [EmailSettingsController::class,'update'])->name('settings.mail.update');
    });
});
