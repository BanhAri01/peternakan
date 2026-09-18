<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EggSaleController;
use App\Http\Controllers\EggGradeController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\CoopController;
use App\Http\Controllers\ExportPdfController;
use App\Http\Controllers\FeedStockController;


Route::get('/', [DashboardController::class, 'index'])->name('owner.dashboard');

// Input Panen Lapangan
Route::get('/panen/input', [DailyLogController::class, 'create'])->name('daily-logs.create');
Route::post('/panen/simpan', [DailyLogController::class, 'store'])->name('daily-logs.store');

// Penjualan & Piutang
Route::get('/penjualan', [EggSaleController::class, 'index'])->name('sales.index');
Route::post('/penjualan/simpan', [EggSaleController::class, 'store'])->name('sales.store');
Route::post('/penjualan/{sale}/bayar-piutang', [EggSaleController::class, 'payDebt'])->name('sales.pay-debt');

// Master Grade Telur (Khusus Owner)
Route::get('/kategori-grade', [EggGradeController::class, 'index'])->name('grades.index');
Route::post('/kategori-grade/simpan', [EggGradeController::class, 'store'])->name('grades.store');
Route::post('/kategori-grade/{grade}/toggle', [EggGradeController::class, 'toggleStatus'])->name('grades.toggle');



// Modul Pengadaan Pakan & Kulakan Telur
Route::get('/pengadaan', [ProcurementController::class, 'index'])->name('procurement.index');
Route::post('/pengadaan/kulakan-telur', [ProcurementController::class, 'storeEggPurchase'])->name('procurement.egg-purchase.store');
Route::post('/pengadaan/restock-pakan', [ProcurementController::class, 'storeFeedPurchase'])->name('procurement.feed-purchase.store');

// Laporan Arus Kas, Laba Rugi & Umur Piutang
Route::get('/laporan-keuangan', [FinancialReportController::class, 'index'])->name('financial.index');

// Ekspor & Cetak Dokumen PDF
Route::get('/penjualan/{sale}/cetak-nota', [ExportPdfController::class, 'printReceipt'])->name('sales.print-receipt');
Route::get('/laporan/ekspor-pdf', [ExportPdfController::class, 'exportMonthlyReport'])->name('reports.monthly-pdf');

Route::resource('coops', CoopController::class);

Route::resource('feed-stocks', FeedStockController::class);