<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WarrantyCheckController;
use App\Http\Controllers\SuperAdmin\ReviewModerationController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\StaleStockController;
use App\Http\Controllers\SuperAdmin\SecurityController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboard;

// Public Routes
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/katalog', [PublicController::class, 'catalog'])->name('catalog');
Route::get('/akun/{account_code}', [PublicController::class, 'detail'])->name('account.detail');
Route::post('/track/event', [PublicController::class, 'trackEvent'])->name('track.event');
Route::get('/cara-membeli', [PublicController::class, 'howToBuy'])->name('how.to.buy');
Route::get('/garansi', [PublicController::class, 'warranty'])->name('warranty');

// Cek sisa garansi pembeli lewat kode transaksi (data dari tabel sales)
Route::get('/cek-garansi', [WarrantyCheckController::class, 'index'])
    ->middleware('throttle:20,1')
    ->name('warranty.check');
Route::get('/bandingkan', [PublicController::class, 'compare'])->name('compare');

// Form ulasan pembeli (diakses lewat link pribadi dari admin)
Route::get('/ulasan/{token}', [ReviewController::class, 'show'])->name('review.form');
Route::post('/ulasan/{token}', [ReviewController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('review.store');

// Upload fallback: digunakan jika Apache tidak menemukan file fisiknya
Route::get('/uploads/{path}', [PublicController::class, 'upload'])
    ->where('path', '(accounts|banners)/.*')
    ->name('uploads.fallback');

// Auth Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// Langkah kedua login Super Admin (kode authenticator / kode pemulihan)
Route::get('/login/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.challenge');
Route::post('/login/two-factor', [TwoFactorController::class, 'verify'])
    ->middleware('throttle:20,1')
    ->name('two-factor.verify');
Route::post('/login/two-factor/cancel', [TwoFactorController::class, 'cancel'])->name('two-factor.cancel');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes (Regular Admin + Super Admin)
Route::middleware(['auth', 'role:admin', 'two-factor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AccountController::class, 'index'])
            ->name('dashboard');

        Route::resource('accounts', AccountController::class);

        Route::post('accounts/{account}/status', [
            AccountController::class,
            'updateStatus'
        ])->name('accounts.status');

        // Form data pembeli saat akun terjual (garansi 7 hari)
        Route::get('accounts/{account}/sell', [
            AccountController::class,
            'sell'
        ])->name('accounts.sell');

        Route::post('accounts/{account}/sell', [
            AccountController::class,
            'storeSale'
        ])->name('accounts.sell.store');

        // Lengkapi / ubah data pembeli pada transaksi yang sudah ada
        Route::get('sales/{sale}/buyer', [
            AccountController::class,
            'buyer'
        ])->name('sales.buyer');

        Route::put('sales/{sale}/buyer', [
            AccountController::class,
            'updateBuyer'
        ])->name('sales.buyer.update');

        Route::delete('accounts/{account}/images/{image}', [
            AccountController::class,
            'destroyImage'
        ])->name('accounts.images.destroy');

        // Banner beranda (slider)
        Route::resource('banners', BannerController::class)->except(['show']);

        Route::post('banners/{banner}/toggle', [
            BannerController::class,
            'toggle'
        ])->name('banners.toggle');

        // Pengingat stok lama + saran penurunan harga
        Route::get('stale-stock', [StaleStockController::class, 'index'])
            ->name('stale.index');

        Route::post('stale-stock/{account}/apply-price', [StaleStockController::class, 'applyPrice'])
            ->name('stale.apply');
    });

// Pengaturan keamanan Super Admin (2FA). Sengaja di luar grup di bawah:
// halaman ini harus bisa dibuka sebelum 2FA aktif, tapi tetap memeriksa sesi bila sudah aktif.
Route::middleware(['auth', 'role:super_admin', 'two-factor:setup'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/security', [SecurityController::class, 'index'])->name('security');

        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/security/two-factor/refresh', [SecurityController::class, 'refresh'])->name('security.refresh');
            Route::post('/security/two-factor/enable', [SecurityController::class, 'enable'])->name('security.enable');
            Route::post('/security/two-factor/recovery', [SecurityController::class, 'regenerateRecovery'])->name('security.recovery');
            Route::post('/security/two-factor/disable', [SecurityController::class, 'disable'])->name('security.disable');
        });
    });

// Super Admin Routes
Route::middleware(['auth', 'role:super_admin', 'two-factor'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/dashboard', [SuperAdminDashboard::class, 'index'])
            ->name('dashboard');

        Route::get('/analytics', [SuperAdminDashboard::class, 'analytics'])
            ->name('analytics');

        Route::get('/sales', [SuperAdminDashboard::class, 'sales'])
            ->name('sales');

        // Ekspor Excel/PDF: daftar transaksi dan laporan bulanan
        Route::get('/sales/export', [ReportController::class, 'exportSales'])
            ->name('sales.export');

        Route::get('/reports/monthly', [ReportController::class, 'monthly'])
            ->name('reports.monthly');

        Route::get('/reports/monthly/export', [ReportController::class, 'exportMonthly'])
            ->name('reports.monthly.export');

        Route::get('/admins', [SuperAdminDashboard::class, 'manageAdmins'])
            ->name('admins');

        // Riwayat aktivitas admin
        Route::get('/activity-logs', [SuperAdminDashboard::class, 'activityLogs'])
            ->name('activity-logs');

        // Konfirmasi penjualan oleh Super Admin
        Route::post('/sales/confirm', [
            SuperAdminDashboard::class,
            'confirmSale'
        ])->name('sales.confirm');

        // Moderasi ulasan pembeli
        Route::get('/reviews', [ReviewModerationController::class, 'index'])->name('reviews');
        Route::post('/reviews/{review}/approve', [ReviewModerationController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{review}/reject', [ReviewModerationController::class, 'reject'])->name('reviews.reject');
        Route::delete('/reviews/{review}', [ReviewModerationController::class, 'destroy'])->name('reviews.destroy');

        // Tambahkan route CRUD admin dan settings di sini nanti
    });