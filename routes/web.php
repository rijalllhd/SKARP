<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryExportController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/analitic', [DashboardController::class, 'analitic'])->name('analitic');
Route::get('/riwayat', [DashboardController::class, 'riwayat'])->name('riwayat');
Route::get('/riwayat/export/excel', [HistoryExportController::class, 'excel'])->name('riwayat.export.excel');
Route::get('/riwayat/export/pdf', [HistoryExportController::class, 'pdf'])->name('riwayat.export.pdf');
