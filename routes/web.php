<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompanySettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadActivityController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserInvitationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WebsiteLeadDemoController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\CsvExportController;
use App\Http\Controllers\WebsiteLeadWebhookController;
use App\Http\Controllers\ChannelConnectionController;
use App\Http\Controllers\ChannelWebhookController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\DashboardTourController;
use App\Http\Controllers\Demo\DemoLoginController;

Route::post('/webhooks/leads/website', [WebsiteLeadWebhookController::class, 'store'])
    ->middleware(['website-lead-webhook', 'throttle:website-leads'])
    ->name('webhooks.leads.website');

Route::match(['get', 'post'], '/webhooks/channels/{uuid}', [ChannelWebhookController::class, 'inbound'])
    ->middleware(['throttle:channel-webhooks'])
    ->name('webhooks.channels.inbound');

require __DIR__.'/marketing.php';

Route::get('/demo', [DemoLoginController::class, 'create'])->name('demo.select');
Route::post('/demo', [DemoLoginController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('demo.start');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified.when_required', 'active', 'company'])
    ->name('dashboard');

Route::middleware(['auth', 'verified.when_required', 'active', 'company'])->group(function () {
    Route::post('/dashboard/tour/complete', [DashboardTourController::class, 'complete'])
        ->name('dashboard.tour.complete');
    Route::post('/dashboard/tour/restart', [DashboardTourController::class, 'restart'])
        ->name('dashboard.tour.restart');
});

// Docs are readable by any active authenticated user (tenant or Super Admin).
Route::middleware(['auth', 'verified.when_required', 'active'])->group(function () {
    Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
    // PDF routes must be registered before the catch-all show route.
    Route::get('/docs/pdf', [DocsController::class, 'downloadAll'])->name('docs.pdf');
    Route::get('/docs/pdf/{path}', [DocsController::class, 'download'])
        ->where('path', '.*')
        ->name('docs.pdf.page');
    Route::get('/docs/{path}', [DocsController::class, 'show'])
        ->where('path', '.*')
        ->name('docs.show');
});

Route::middleware(['auth', 'verified.when_required', 'active', 'company'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/notification-preferences', [ProfileController::class, 'updateNotificationPreferences'])
        ->name('profile.notification-preferences.update');
    Route::patch('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::delete('/profile/sessions/others', [\App\Http\Controllers\ProfileSessionController::class, 'destroyOthers'])->name('profile.sessions.destroy-others');
    Route::delete('/profile/sessions/{session}', [\App\Http\Controllers\ProfileSessionController::class, 'destroy'])->name('profile.sessions.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/company', [CompanySettingsController::class, 'show'])->name('company.profile');
    Route::get('/company/settings', [CompanySettingsController::class, 'edit'])->name('company.settings.edit');
    Route::patch('/company/settings', [CompanySettingsController::class, 'update'])->name('company.settings.update');

    Route::get('/search', [GlobalSearchController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('search.index');
    Route::get('/search/suggest', [GlobalSearchController::class, 'suggest'])
        ->middleware('throttle:60,1')
        ->name('search.suggest');

    Route::middleware('permission:view.notifications')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    });

    Route::get('/imports/{type}', [CsvImportController::class, 'create'])
        ->whereIn('type', ['leads', 'customers', 'users'])
        ->name('imports.create');
    Route::post('/imports/{type}', [CsvImportController::class, 'store'])
        ->whereIn('type', ['leads', 'customers', 'users'])
        ->name('imports.store');
    Route::get('/imports/{type}/sample', [CsvImportController::class, 'sample'])
        ->whereIn('type', ['leads', 'customers', 'users'])
        ->name('imports.sample');

    Route::get('/leads/export', [CsvExportController::class, 'leads'])
        ->middleware('throttle:30,1')
        ->name('exports.leads');
    Route::get('/customers/export', [CsvExportController::class, 'customers'])
        ->middleware('throttle:30,1')
        ->name('exports.customers');
    Route::get('/tasks/export', [CsvExportController::class, 'tasks'])
        ->middleware('throttle:30,1')
        ->name('exports.tasks');
    Route::get('/users/export', [CsvExportController::class, 'users'])
        ->middleware('throttle:30,1')
        ->name('exports.users');

    Route::resource('customers', CustomerController::class);

    Route::resource('leads', LeadController::class);
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convertToCustomer'])
        ->name('leads.convert');
    Route::post('/leads/board/update', [LeadController::class, 'updateBoard'])
        ->name('leads.board.update');
    Route::post('/leads/{lead}/activities', [LeadActivityController::class, 'store'])
        ->name('leads.activities.store');

    Route::resource('tasks', TaskController::class);
    Route::post('/tasks/{task}/status', [TaskController::class, 'changeStatus'])
        ->name('tasks.status');
    Route::post('/tasks/board/update', [TaskController::class, 'updateBoard'])
        ->name('tasks.board.update');

    Route::resource('users', UserController::class);
    Route::get('/users-invite/create', [UserInvitationController::class, 'create'])
        ->name('users.invite.create');
    Route::post('/users-invite', [UserInvitationController::class, 'store'])
        ->name('users.invite.store');
    Route::post('/users/{user}/status', [UserController::class, 'changeStatus'])
        ->name('users.status');

    Route::resource('roles', RoleController::class)->except(['show']);

    Route::resource('channels', ChannelConnectionController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('/channels/{channel}/test', [ChannelConnectionController::class, 'test'])
        ->name('channels.test');
    Route::post('/channels/{channel}/sync', [ChannelConnectionController::class, 'sync'])
        ->name('channels.sync');
    Route::post('/channels/{channel}/retry', [ChannelConnectionController::class, 'retry'])
        ->name('channels.retry');
    Route::post('/channels/{channel}/disconnect', [ChannelConnectionController::class, 'disconnect'])
        ->name('channels.disconnect');
    Route::post('/channels/{channel}/regenerate-secret', [ChannelConnectionController::class, 'regenerateSecret'])
        ->name('channels.regenerate-secret');

    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{conversation}', [InboxController::class, 'show'])->name('inbox.show');
    Route::post('/inbox/{conversation}/reply', [InboxController::class, 'reply'])->name('inbox.reply');
    Route::post('/inbox/{conversation}/assign', [InboxController::class, 'assign'])->name('inbox.assign');
    Route::post('/inbox/{conversation}/status', [InboxController::class, 'updateStatus'])->name('inbox.status');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{type}', [ReportController::class, 'export'])
        ->middleware('throttle:30,1')
        ->name('reports.export');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified.when_required', 'active', 'company', 'permission:website_lead.demo'])->group(function () {
    Route::get('/demo/website-lead', [WebsiteLeadDemoController::class, 'index'])
        ->name('demo.website-lead');
    Route::post('/demo/website-lead', [WebsiteLeadDemoController::class, 'store'])
        ->name('demo.website-lead.store');
});
