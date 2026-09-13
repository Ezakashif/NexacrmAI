<?php

use App\Http\Controllers\Marketing\AboutController;
use App\Http\Controllers\Marketing\ContactController;
use App\Http\Controllers\Marketing\DocumentationController;
use App\Http\Controllers\Marketing\FeaturesController;
use App\Http\Controllers\Marketing\HomeController;
use App\Http\Controllers\Marketing\PricingController;
use App\Http\Controllers\Marketing\RobotsController;
use App\Http\Controllers\Marketing\SitemapController;
use App\Http\Controllers\Marketing\WatchDemoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('marketing.home');
Route::get('/features', [FeaturesController::class, 'index'])->name('marketing.features');
Route::get('/pricing', [PricingController::class, 'index'])->name('marketing.pricing');
Route::get('/documentation', [DocumentationController::class, 'index'])->name('marketing.documentation');
Route::get('/documentation/pdf', [DocumentationController::class, 'downloadAll'])->name('marketing.documentation.pdf');
Route::get('/documentation/pdf/{path}', [DocumentationController::class, 'download'])
    ->where('path', '.*')
    ->name('marketing.documentation.pdf.page');
Route::get('/documentation/{path}', [DocumentationController::class, 'show'])
    ->where('path', '.*')
    ->name('marketing.documentation.show');
Route::get('/about', [AboutController::class, 'index'])->name('marketing.about');
Route::get('/watch-demo', [WatchDemoController::class, 'index'])->name('marketing.watch-demo');
Route::get('/contact', [ContactController::class, 'create'])->name('marketing.contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('marketing.contact.store');
Route::get('/sitemap.xml', SitemapController::class)->name('marketing.sitemap');
Route::get('/robots.txt', RobotsController::class)->name('marketing.robots');
