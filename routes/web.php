<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;

// Google OAuth & Gmail
use App\Http\Controllers\Noc\GoogleAuthController;
use App\Http\Controllers\Noc\EmailController;

// Core & Other Controllers
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketUpdateController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SLAController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EscalationController;
use App\Http\Controllers\NocController;
use App\Http\Controllers\CactiGraphController;
use App\Http\Controllers\WhatsappBotController;
use App\Http\Controllers\NmsController;
use App\Http\Controllers\MonitoringController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1) Redirect root to login
Route::redirect('/', '/login');

// 2) Public auth
Route::get('/login',    [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login',   [AuthenticatedSessionController::class, 'store']);
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register',[RegisteredUserController::class, 'store']);

// 3) Public Google OAuth
Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

    Route::get('reports/export-pdf',   [ReportController::class,'exportPdf'])->name('reports.exportPdf');
    Route::get('reports/print',        [ReportController::class,'printPreview'])->name('reports.print');
    Route::get('reports/export-spout', [ReportController::class,'exportSpout'])->name('reports.exportSpout');
// 4) Protected routes
Route::middleware('auth')->group(function () {

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* Tickets */
    Route::post('/tickets/import',         [TicketController::class, 'import'])->name('tickets.import');
    Route::get('/tickets/export-template', [TicketController::class, 'exportTemplate'])->name('tickets.export.template');
    Route::get('/tickets/export',          [TicketController::class, 'exportTickets'])->name('tickets.export');
    Route::patch('/tickets/{ticket}/close',[TicketController::class, 'close'])->name('tickets.close');
    Route::post('/tickets/{ticket}/updates',[TicketUpdateController::class, 'store'])->name('tickets.updates.store');
    Route::resource('tickets', TicketController::class);
    Route::get('/tickets/{ticket}/rfo',              [TicketController::class,'rfo'])->name('tickets.rfo');
    Route::match(['get','post'], '/tickets/{ticket}/rfo/pdf', [TicketController::class,'rfoPdf'])->name('tickets.rfo.pdf');
    Route::patch('/tickets/{ticket}/chronology',       [TicketController::class,'updateChronology'])->name('tickets.chronology.update');

    /* Customers */
    Route::get('customers/json',   [CustomerController::class,'json'])->name('customers.json');
    Route::get('customers-export', [CustomerController::class,'export'])->name('customers.export');
    Route::resource('customers', CustomerController::class);

    /* Reports */
    Route::get('reports',              [ReportController::class,'index'])->name('reports.index');

    /* SLA & Performance */
    Route::get('sla',                         [SLAController::class,'index'])->name('sla.index');
    Route::get('sla/device/{device}',         [SLAController::class,'device'])->name('sla.device');
    Route::get('sla/{sensorId}',              [SLAController::class,'show'])->whereNumber('sensorId')->name('sla.show');
    Route::get('/sla/export', [SLAController::class, 'export'])->name('sla.export');
    Route::get('sla/{sensorId}/download-pdf', [SLAController::class,'downloadPdf'])->name('sla.downloadPdf');
    Route::get('/performance',               [PerformanceController::class,'index'])->name('performance.index');
    Route::get('/performance/eval',          [PerformanceController::class,'evalDashboard'])->name('performance.eval');
    Route::get('/performance/detail/{type}', [PerformanceController::class,'detail'])->name('performance.detail');

    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');

    // JSON endpoints dipanggil dari front-end
    Route::get('/monitoring/devices.json',     [MonitoringController::class, 'devicesJson']);
    Route::get('/monitoring/interfaces.json',  [MonitoringController::class, 'interfacesJson']);
    Route::get('/monitoring/graph.png',        [MonitoringController::class, 'graphPng']);
});

    /* User & Role Management */
    Route::middleware('can:manage users')->group(function () {
        Route::post('users/{user}/reset-password', [UserController::class,'resetPassword'])->name('users.resetPassword');
        Route::resource('users', UserController::class);
        Route::get('users/{user}/roles', [RoleController::class,'assignRoleForm'])->name('users.roles.edit');
        Route::post('users/{user}/roles',[RoleController::class,'assignRole'])->name('users.roles.update');
        Route::resource('roles', RoleController::class);
    });

    /* NOC Shift & Handover */
    Route::prefix('noc')->name('noc.')->group(function () {
        Route::get('manage-shifts',  [NocController::class,'manageShifts'])->name('manageShifts');
        Route::post('update-shifts', [NocController::class,'updateShifts'])->name('updateShifts');
        Route::get('handover',       [NocController::class,'handover'])->name('handover');
        Route::post('store-handover',[NocController::class,'storeHandover'])->name('storeHandover');
        Route::get('history',        [NocController::class,'history'])->name('history');
    });

    /* Escalations */
    Route::get('/escalations',             [EscalationController::class,'index'])->name('escalations.index')->middleware('can:manage escalation');
    Route::post('/escalations',            [EscalationController::class,'store'])->name('escalations.store')->middleware('can:manage escalation');
    Route::post('tickets/{ticket}/escalate',[EscalationController::class,'send'])->name('tickets.escalate')->middleware('can:manage escalation');

    /* Gmail Inbox, Folders, Compose, Reply & Unread‐Count */
    Route::middleware('can:manage settings')->group(function () {
        // Alias to inbox
        Route::get('settings/mail',                      [EmailController::class,'inbox'])->name('settings.mail');
        // Folders
        Route::get('settings/mail/inbox',                [EmailController::class,'inbox'])->name('settings.mail.inbox');
        Route::get('settings/mail/sent',                 [EmailController::class,'sent'])->name('settings.mail.sent');
        Route::get('settings/mail/spam',                 [EmailController::class,'spam'])->name('settings.mail.spam');
        // Compose
        Route::get('settings/mail/create',               [EmailController::class,'create'])->name('settings.mail.create');
        Route::post('settings/mail',                     [EmailController::class,'store'])->name('settings.mail.store');
        // Show & Reply
        Route::get('settings/mail/{id}',                 [EmailController::class,'show'])->name('settings.mail.show');
        Route::post('settings/mail/{id}/reply',          [EmailController::class,'reply'])->name('settings.mail.reply');
        // Unread‐count API
        Route::get('api/mail/unread-count',              [EmailController::class,'unreadCount'])->name('api.mail.unreadCount');
    });

    /* Network Monitoring & Bot */
    Route::get('/cacti-graphs',           [CactiGraphController::class,'index'])->name('cacti.graphs.index');
    Route::get('/cacti-graphs/{id}',      [CactiGraphController::class,'show'])->name('cacti.graphs.show');
    Route::get('/cacti-proxy/image/{id}', [CactiGraphController::class,'image'])->name('cacti.graphs.image');
    Route::get('/cacti-proxy/export/{id}',[CactiGraphController::class,'export'])->name('cacti.graphs.export');
    Route::get('whatsapp-bot',            [WhatsappBotController::class,'index'])->name('whatsapp.bot');
    Route::get('whatsapp-bot/session',    [WhatsappBotController::class,'session']);
    Route::post('whatsapp-bot/send',      [WhatsappBotController::class,'send']);



