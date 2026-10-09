<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\FarmController as AdminFarmController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\OtherIncomeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\CoopController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EggGradeController;
use App\Http\Controllers\EggSaleController;
use App\Http\Controllers\EggSortingController;
use App\Http\Controllers\ExpenseLedgerController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ExportPdfController;
use App\Http\Controllers\FeedStockController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VaccinationController;

Route::get('/', HomeController::class)->name('home');
Route::get('/tentang-kami', [LegalController::class, 'about'])->name('legal.about');
Route::get('/syarat-ketentuan', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/kebijakan-privasi', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/kebijakan-pengembalian-dana', [LegalController::class, 'refund'])->name('legal.refund');
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/pwa/offline.js', [PwaController::class, 'script'])->name('pwa.script');
Route::get('/pwa/ikon/{name}', [PwaController::class, 'icon'])->name('pwa.icon');
Route::get('/pwa/tanpa-sinyal', [PwaController::class, 'offline'])->name('pwa.offline');
Route::post('/webhook/pembayaran', [SubscriptionController::class, 'webhook'])->middleware('throttle:120,1')->name('webhooks.payment');

// =========================================================================
// 1. TAMU (BELUM LOGIN)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/login-worker', [AuthController::class, 'loginWorker'])->name('login.worker');

    // Pendaftaran peternakan baru (SaaS)
    Route::get('/daftar', [RegisterController::class, 'create'])->name('register');
    Route::post('/daftar', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');

    Route::get('/lupa-sandi', [PasswordResetController::class, 'request'])->name('password.request');
});

// =========================================================================
// 2. SETELAH LOGIN
// =========================================================================
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/bantuan/selesai', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    Route::get('/sesi/token', fn () => response()
        ->json(['token' => csrf_token(), 'user' => auth()->id()])
        ->header('Cache-Control', 'no-store'))->name('session.token');

    // Halaman depan sesuai peran
    // Info langganan (tetap bisa dibuka pemilik walau masa langganan habis)
    Route::middleware('role:owner')->group(function () {
        Route::get('/langganan', [SubscriptionController::class, 'show'])->name('subscription.show');
        Route::post('/langganan/bayar', [SubscriptionController::class, 'pay'])->middleware('throttle:10,1')->name('subscription.pay');
        Route::get('/langganan/selesai', [SubscriptionController::class, 'finish'])->name('subscription.finish');
        Route::get('/langganan/simulasi/{reference}', [SubscriptionController::class, 'simulate'])->middleware('signed')->name('subscription.simulate');
        Route::post('/langganan/simulasi/{reference}', [SubscriptionController::class, 'simulateConfirm'])->name('subscription.simulate.confirm');
    });

    // ---------------------------------------------------------------------
    // ADMIN HEFAM: kelola semua peternakan
    // ---------------------------------------------------------------------
    Route::middleware('role:superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/peternakan', [AdminFarmController::class, 'index'])->name('farms.index');
        Route::get('/peternakan/baru', [AdminFarmController::class, 'create'])->name('farms.create');
        Route::post('/peternakan', [AdminFarmController::class, 'store'])->name('farms.store');
        Route::get('/peternakan/{farm}', [AdminFarmController::class, 'edit'])->name('farms.edit');
        Route::put('/peternakan/{farm}', [AdminFarmController::class, 'update'])->name('farms.update');
        Route::post('/peternakan/{farm}/perpanjang', [AdminFarmController::class, 'extend'])->name('farms.extend');
        Route::post('/peternakan/{farm}/sandi-baru', [AdminFarmController::class, 'resetOwnerPassword'])->name('farms.reset-password');
        Route::post('/peternakan/{farm}/masuk', [ImpersonationController::class, 'start'])->name('farms.impersonate');

        Route::get('/backup', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backup', [BackupController::class, 'store'])->middleware('throttle:3,10')->name('backups.store');
        Route::get('/backup/{file}', [BackupController::class, 'download'])->where('file', 'hefam-[0-9\-]+\.sql\.gz')->name('backups.download');
    });

    // ---------------------------------------------------------------------
    // PEKERJA & OWNER: catat panen harian
    // ---------------------------------------------------------------------
    Route::middleware(['farm', 'role:worker,owner'])->group(function () {
        Route::get('/panen/input', [DailyLogController::class, 'create'])->name('daily-logs.create');
        Route::post('/panen/simpan', [DailyLogController::class, 'store'])->name('daily-logs.store');

        // Sortir telur campur menjadi per jenis
        Route::get('/sortir', [EggSortingController::class, 'create'])->name('sortings.create');
        Route::post('/sortir', [EggSortingController::class, 'store'])->name('sortings.store');
    });

    // ---------------------------------------------------------------------
    // KHUSUS OWNER
    // ---------------------------------------------------------------------
    Route::middleware(['farm', 'role:owner'])->group(function () {

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
        Route::get('/pengadaan/pakan/{feedPurchase}/ubah', [ProcurementController::class, 'editFeedPurchase'])->name('procurement.feed-purchase.edit');
        Route::put('/pengadaan/pakan/{feedPurchase}', [ProcurementController::class, 'updateFeedPurchase'])->name('procurement.feed-purchase.update');
        Route::delete('/pengadaan/pakan/{feedPurchase}', [ProcurementController::class, 'destroyFeedPurchase'])->name('procurement.feed-purchase.destroy');

        Route::resource('pemasok', SupplierController::class)
            ->only(['index', 'edit', 'update'])
            ->parameters(['pemasok' => 'supplier'])
            ->names('suppliers');

        // Laporan
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/ekspor-pdf', [ExportPdfController::class, 'exportMonthlyReport'])->name('reports.monthly-pdf');
        Route::middleware('feature:export')->group(function () {
            Route::get('/ekspor', [ExportController::class, 'index'])->name('exports.index');
            Route::get('/ekspor/unduh', [ExportController::class, 'download'])->middleware('throttle:10,1')->name('exports.download');
        });

        // Pengaturan peternakan
        Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/pengaturan/contoh-nota', [ExportPdfController::class, 'previewReceipt'])->name('settings.receipt-preview');
        Route::post('/pengaturan/uji-whatsapp', [SettingController::class, 'testWhatsApp'])->middleware('throttle:5,10')->name('settings.whatsapp-test');

        Route::resource('coops', CoopController::class);
        Route::resource('feed-stocks', FeedStockController::class)->except('show');
        Route::resource('vaccinations', VaccinationController::class)->except('show');
        Route::middleware('feature:medicines')->group(function () {
            Route::resource('obat', MedicineController::class)->except('show')->parameters(['obat' => 'medicine'])->names('medicines');
            Route::get('/obat/{medicine}/catat', [MedicineController::class, 'movement'])->name('medicines.movement');
            Route::post('/obat/{medicine}/catat', [MedicineController::class, 'storeMovement'])->name('medicines.movement.store');
            Route::delete('/obat/mutasi/{movement}', [MedicineController::class, 'destroyMovement'])->name('medicines.movement.destroy');
        });
        Route::resource('expenses', ExpenseLedgerController::class)->except('show');
        Route::resource('pendapatan-lain', OtherIncomeController::class)->except('show')->parameters(['pendapatan-lain' => 'otherIncome'])->names('other-incomes')->middleware('feature:other_income');
        Route::resource('users', UserController::class)->except('show');

        Route::middleware('feature:payroll')->group(function () {
            Route::get('/absensi', [AttendanceController::class, 'index'])->name('attendance.index');
            Route::post('/absensi', [AttendanceController::class, 'store'])->name('attendance.store');
            Route::get('/gaji', [PayrollController::class, 'index'])->name('payroll.index');
            Route::post('/gaji/bayar', [PayrollController::class, 'pay'])->name('payroll.pay');
        });

        // HP kandang: perangkat tempat pekerja masuk dengan nama + PIN
        Route::get('/hp-kandang', [DeviceController::class, 'index'])->name('devices.index');
        Route::post('/hp-kandang', [DeviceController::class, 'store'])->name('devices.store');
        Route::put('/hp-kandang/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::delete('/hp-kandang/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::post('/hp-kandang/serahkan', [DeviceController::class, 'handOver'])->name('devices.hand-over');

        Route::get('/riwayat-perubahan', [ActivityLogController::class, 'index'])->name('activity.index');
        Route::get('/sampah', [TrashController::class, 'index'])->name('trash.index');
        Route::post('/sampah/{type}/{id}/pulihkan', [TrashController::class, 'restore'])->whereNumber('id')->name('trash.restore');
    });
});
