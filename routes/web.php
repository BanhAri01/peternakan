<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoopController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EggGradeController;
use App\Http\Controllers\EggSaleController;
use App\Http\Controllers\EggSortingController;
use App\Http\Controllers\ExpenseLedgerController;
use App\Http\Controllers\ExportPdfController;
use App\Http\Controllers\FeedStockController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VaccinationController;

// =========================================================================
// 1. TAMU (BELUM LOGIN)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/login-worker', [AuthController::class, 'loginWorker'])->name('login.worker');
});

// =========================================================================
// 2. SETELAH LOGIN
// =========================================================================
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Halaman depan sesuai peran
    Route::get('/', function () {
        return auth()->user()->isOwner()
            ? redirect()->route('owner.dashboard')
            : redirect()->route('daily-logs.create');
    });

    // ---------------------------------------------------------------------
    // PEKERJA & OWNER: catat panen harian
    // ---------------------------------------------------------------------
    Route::middleware('role:worker,owner')->group(function () {
        Route::get('/panen/input', [DailyLogController::class, 'create'])->name('daily-logs.create');
        Route::post('/panen/simpan', [DailyLogController::class, 'store'])->name('daily-logs.store');

        // Sortir telur campur menjadi per jenis
        Route::get('/sortir', [EggSortingController::class, 'create'])->name('sortings.create');
        Route::post('/sortir', [EggSortingController::class, 'store'])->name('sortings.store');
    });

    // ---------------------------------------------------------------------
    // KHUSUS OWNER
    // ---------------------------------------------------------------------
    Route::middleware('role:owner')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('owner.dashboard');

        // Riwayat panen
        Route::get('/panen', [DailyLogController::class, 'index'])->name('daily-logs.index');
        Route::get('/panen/{dailyLog}/edit', [DailyLogController::class, 'edit'])->name('daily-logs.edit');
        Route::put('/panen/{dailyLog}', [DailyLogController::class, 'update'])->name('daily-logs.update');
        Route::delete('/panen/{dailyLog}', [DailyLogController::class, 'destroy'])->name('daily-logs.destroy');
        Route::delete('/sortir/{sorting}', [EggSortingController::class, 'destroy'])->name('sortings.destroy');

        // Penjualan & piutang
        Route::get('/penjualan', [EggSaleController::class, 'index'])->name('sales.index');
        Route::post('/penjualan/simpan', [EggSaleController::class, 'store'])->name('sales.store');
        Route::post('/penjualan/nota/{invoice}/bayar', [EggSaleController::class, 'payDebt'])->name('sales.pay-debt');
        Route::delete('/penjualan/nota/{invoice}', [EggSaleController::class, 'destroy'])->name('sales.destroy');
        Route::get('/penjualan/nota/{invoice}/cetak', [ExportPdfController::class, 'printReceipt'])->name('sales.print-receipt');

        Route::resource('pelanggan', CustomerController::class)
            ->only(['index', 'show', 'edit', 'update'])
            ->parameters(['pelanggan' => 'customer'])
            ->names('customers');

        // Jenis telur (grade)
        Route::get('/kategori-grade', [EggGradeController::class, 'index'])->name('grades.index');
        Route::post('/kategori-grade/simpan', [EggGradeController::class, 'store'])->name('grades.store');
        Route::put('/kategori-grade/{grade}', [EggGradeController::class, 'update'])->name('grades.update');
        Route::post('/kategori-grade/{grade}/toggle', [EggGradeController::class, 'toggleStatus'])->name('grades.toggle');

        // Belanja pakan & kulakan telur
        Route::get('/pengadaan', [ProcurementController::class, 'index'])->name('procurement.index');
        Route::post('/pengadaan/kulakan-telur', [ProcurementController::class, 'storeEggPurchase'])->name('procurement.egg-purchase.store');
        Route::post('/pengadaan/restock-pakan', [ProcurementController::class, 'storeFeedPurchase'])->name('procurement.feed-purchase.store');

        Route::resource('pemasok', SupplierController::class)
            ->only(['index', 'edit', 'update'])
            ->parameters(['pemasok' => 'supplier'])
            ->names('suppliers');

        // Laporan
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/ekspor-pdf', [ExportPdfController::class, 'exportMonthlyReport'])->name('reports.monthly-pdf');

        // Pengaturan peternakan
        Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');

        Route::resource('coops', CoopController::class);
        Route::resource('feed-stocks', FeedStockController::class)->except('show');
        Route::resource('vaccinations', VaccinationController::class)->except('show');
        Route::resource('expenses', ExpenseLedgerController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
    });
});
