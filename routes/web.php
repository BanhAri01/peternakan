<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EggSaleController;
use App\Http\Controllers\EggGradeController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\CoopController;
use App\Http\Controllers\ExportPdfController;
use App\Http\Controllers\FeedStockController;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Peternakan Layer
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. RUTE TAMU (GUEST ONLY - SEBELUM LOGIN)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/login-worker', [AuthController::class, 'loginWorker'])->name('login.worker'); // <-- Tambahkan ini
});

// =========================================================================
// 2. RUTE SETELAH LOGIN (AUTHENTICATED)
// =========================================================================
Route::middleware('auth')->group(function () {

    // Aksi Keluar (Logout)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Pengalihan Halaman Depan Berdasarkan Role
    Route::get('/', function () {
        if (auth()->user()->isOwner()) {
            return redirect()->route('owner.dashboard');
        }
        return redirect()->route('daily-logs.create');
    });

    // ---------------------------------------------------------------------
    // AKSES BERSAMA: PEKERJA & OWNER (Input Panen Lapangan)
    // ---------------------------------------------------------------------
    Route::middleware('role:worker,owner')->group(function () {
        Route::get('/panen/input', [DailyLogController::class, 'create'])->name('daily-logs.create');
        Route::post('/panen/simpan', [DailyLogController::class, 'store'])->name('daily-logs.store');
    });

    // ---------------------------------------------------------------------
    // AKSES KHUSUS: OWNER (Full Control)
    // ---------------------------------------------------------------------
    Route::middleware('role:owner')->group(function () {

        // Dashboard Eksekutif
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('owner.dashboard');

        // Modul Penjualan & Piutang Telur
        Route::get('/penjualan', [EggSaleController::class, 'index'])->name('sales.index');
        Route::post('/penjualan/simpan', [EggSaleController::class, 'store'])->name('sales.store');
        Route::post('/penjualan/{sale}/bayar-piutang', [EggSaleController::class, 'payDebt'])->name('sales.pay-debt');

        // Master Grade Telur
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

        // CRUD Data Kandang (Coops)
        Route::resource('coops', CoopController::class);

        // CRUD Inventaris Stok Bahan Pakan (Feed Stocks)
        Route::resource('feed-stocks', FeedStockController::class);
    });

});