<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return redirect('/admin');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('report/base',[ReportController::class, 'getBaseReport'])->name('reports.search-query.download');

require __DIR__.'/settings.php';
