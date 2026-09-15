<?php

use App\Http\Controllers\LeaveImpersonationController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\SuperAdmin\AccountController;
use App\Http\Controllers\SuperAdmin\AnalyticsController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\CompanyExportController;
use App\Http\Controllers\SuperAdmin\CompanyImportController;
use App\Http\Controllers\SuperAdmin\ContactInquiryController;
use App\Http\Controllers\SuperAdmin\DemoVisitController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\EmailTemplateController;
use App\Http\Controllers\SuperAdmin\ImpersonationController;
use App\Http\Controllers\SuperAdmin\NotificationController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SearchController;
use App\Http\Controllers\SuperAdmin\SettingsController;
use App\Http\Controllers\SuperAdmin\SuperAdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])
    ->post('impersonation/leave', [LeaveImpersonationController::class, 'store'])
    ->name('impersonation.leave');

Route::middleware(['auth', 'active', 'superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('search', [SearchController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('search.index');
        Route::get('search/suggest', [SearchController::class, 'suggest'])
            ->middleware('throttle:60,1')
            ->name('search.suggest');

        Route::get('analytics/companies', [AnalyticsController::class, 'companies'])
            ->middleware('throttle:60,1')
            ->name('analytics.companies');
        Route::get('analytics/leads', [AnalyticsController::class, 'leads'])
            ->middleware('throttle:60,1')
            ->name('analytics.leads');
        Route::get('analytics/customers', [AnalyticsController::class, 'customers'])
            ->middleware('throttle:60,1')
            ->name('analytics.customers');

        Route::get('companies/export', [CompanyExportController::class, 'csv'])
            ->middleware('throttle:30,1')
            ->name('companies.export');
        Route::get('companies/export/pdf', [CompanyExportController::class, 'pdf'])
            ->middleware('throttle:30,1')
            ->name('companies.export.pdf');

        Route::get('companies/import', [CompanyImportController::class, 'create'])
            ->name('companies.import.create');
        Route::post('companies/import', [CompanyImportController::class, 'store'])
            ->name('companies.import.store');
        Route::get('companies/import/sample', [CompanyImportController::class, 'sample'])
            ->name('companies.import.sample');

        Route::resource('companies', CompanyController::class);
        Route::patch('companies/{company}/status', [CompanyController::class, 'updateStatus'])
            ->name('companies.status');
        Route::post('companies/{company}/restore', [CompanyController::class, 'restore'])
            ->name('companies.restore');
        Route::get('companies/{company}/pdf', [CompanyController::class, 'pdf'])
            ->middleware('throttle:30,1')
            ->name('companies.pdf');
        Route::post('companies/{company}/impersonate', [ImpersonationController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('companies.impersonate');

        Route::get('plans/export', [PlanController::class, 'export'])->middleware('throttle:30,1')->name('plans.export');
        Route::get('plans/import/sample', [PlanController::class, 'sampleImport'])
            ->middleware('throttle:30,1')
            ->name('plans.import.sample');
        Route::post('plans/import', [PlanController::class, 'import'])->name('plans.import');
        Route::post('plans/bulk', [PlanController::class, 'bulk'])->name('plans.bulk');
        Route::post('plans/{plan}/duplicate', [PlanController::class, 'duplicate'])->name('plans.duplicate');
        Route::resource('plans', PlanController::class)->except(['show']);

        Route::get('super-admins', [SuperAdminUserController::class, 'index'])->name('super-admins.index');
        Route::get('super-admins/create', [SuperAdminUserController::class, 'create'])->name('super-admins.create');
        Route::post('super-admins', [SuperAdminUserController::class, 'store'])->name('super-admins.store');

        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::put('settings/announcement', [SettingsController::class, 'announcement'])->name('settings.announcement');

        Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::get('email-templates/{email_template}/preview', [EmailTemplateController::class, 'preview'])
            ->name('email-templates.preview');
        Route::post('email-templates/{email_template}/test', [EmailTemplateController::class, 'sendTest'])
            ->middleware('throttle:10,1')
            ->name('email-templates.test');
        Route::resource('email-templates', EmailTemplateController::class)->except(['show']);

        Route::get('contact-inquiries', [ContactInquiryController::class, 'index'])
            ->name('contact-inquiries.index');
        Route::get('contact-inquiries/{contact_inquiry}', [ContactInquiryController::class, 'show'])
            ->name('contact-inquiries.show');
        Route::patch('contact-inquiries/{contact_inquiry}/status', [ContactInquiryController::class, 'updateStatus'])
            ->name('contact-inquiries.status');

        Route::get('demo-visits', [DemoVisitController::class, 'index'])
            ->name('demo-visits.index');
        Route::get('demo-visits/{demo_visit}', [DemoVisitController::class, 'show'])
            ->name('demo-visits.show');
        Route::patch('demo-visits/{demo_visit}/status', [DemoVisitController::class, 'updateStatus'])
            ->name('demo-visits.status');

        Route::get('notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
        Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');
    });
