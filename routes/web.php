<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WarrantyCheckController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\SuperAdmin\CustomerController;
use App\Http\Controllers\SuperAdmin\MaintenanceController;
use App\Http\Controllers\SuperAdmin\ReviewModerationController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\StaleStockController;
use App\Http\Controllers\SuperAdmin\SecurityController;
use App\Http\Controllers\SuperAdmin\UnsoldAccountController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboard;

// Public Routes
// Tombol pada layout untuk kembali ke halaman pengaturan maintenance.
Route::get('/maintenance/exit', function () {
    return redirect()->route('super-admin.maintenance');
})->name('maintenance.exit');

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/katalog', [PublicController::class, 'catalog'])->name('catalog');
Route::get('/akun/{account_code}', [PublicController::class, 'detail'])->name('account.detail');
Route::post('/track/event', [PublicController::class, 'trackEvent'])->name('track.event');
// Klik "Beli via WhatsApp" per akun (dipanggil JS di halaman detail, dipakai untuk social proof & analitik)
Route::post('/akun/{account_code}/klik-wa', [PublicController::class, 'trackWhatsappClick'])
    ->middleware('throttle:20,1')
    ->name('track.wa');
Route::get('/cara-membeli', [PublicController::class, 'howToBuy'])->name('how.to.buy');
Route::get('/garansi', [PublicController::class, 'warranty'])->name('warranty');

// Booking akun dengan DP: syarat & ketentuan, buat booking, halaman booking pembeli
Route::get('/syarat-dp', [BookingController::class, 'terms'])->name('booking.terms');
Route::post('/akun/{account_code}/booking', [BookingController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('booking.store');
Route::get('/booking/{token}', [BookingController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('booking.show');

// Token CSRF segar untuk form yang dibiarkan terbuka lama (dipanggil otomatis oleh JS).
// Sekaligus memperpanjang masa aktif sesi.
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

// Cek sisa garansi pembeli lewat kode transaksi (data dari tabel sales)
Route::get('/cek-garansi', [WarrantyCheckController::class, 'index'])
    ->middleware('throttle:20,1')
    ->name('warranty.check');

// Invoice pembeli: link pribadi dari admin + cadangan verifikasi 4 digit nomor WA
Route::get('/invoice/{token}', [InvoiceController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('invoice.show');
Route::post('/cek-garansi/invoice', [InvoiceController::class, 'verify'])
    ->middleware('throttle:8,1')
    ->name('invoice.verify');

Route::get('/bandingkan', [PublicController::class, 'compare'])->name('compare');

// Form ulasan pembeli (diakses lewat link pribadi dari admin)
Route::get('/ulasan/{token}', [ReviewController::class, 'show'])->name('review.form');
Route::post('/ulasan/{token}', [ReviewController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('review.store');

// Upload fallback: digunakan jika Apache tidak menemukan file fisiknya
Route::get('/uploads/{path}', [PublicController::class, 'upload'])
    ->where('path', '(accounts|banners|maintenance)/.*')
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

        // Hapus banyak foto sekaligus (pilih semua)
        Route::delete('accounts/{account}/images', [
            AccountController::class,
            'destroyImages'
        ])->name('accounts.images.bulk-destroy');

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

        // Catat booking DP langsung dari Kelola Akun (pembeli bayar DP dulu, lunas 7 / 14 hari)
        Route::get('accounts/{account}/booking', [AdminBookingController::class, 'createForAccount'])->name('accounts.booking');
        Route::post('accounts/{account}/booking', [AdminBookingController::class, 'storeForAccount'])->name('accounts.booking.store');

        // Booking akun (DP 20%): konfirmasi DP, perpanjang, batalkan
        Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::post('bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])->name('bookings.confirm');
        Route::post('bookings/{booking}/extend', [AdminBookingController::class, 'extend'])->name('bookings.extend');
        Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('bookings/{booking}/resolve', [AdminBookingController::class, 'resolve'])->name('bookings.resolve');

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

        // Akun yang belum terjual + total harganya
        Route::get('/unsold', [UnsoldAccountController::class, 'index'])
            ->name('unsold');

        // Pelanggan: riwayat beli per nomor WhatsApp, pembeli berulang, dan catatan
        Route::get('/customers', [CustomerController::class, 'index'])
            ->name('customers');
        Route::get('/customers/{phone}', [CustomerController::class, 'show'])
            ->where('phone', '[0-9]{8,16}')
            ->name('customers.show');
        Route::put('/customers/{phone}/note', [CustomerController::class, 'updateNote'])
            ->where('phone', '[0-9]{8,16}')
            ->name('customers.note');

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

        // Hapus riwayat aktivitas (satu, terpilih, atau dibersihkan massal dengan password)
        Route::post('/activity-logs/bulk-delete', [ActivityLogController::class, 'bulkDestroy'])->name('activity-logs.bulk-delete');
        Route::post('/activity-logs/clear', [ActivityLogController::class, 'clear'])
            ->middleware('throttle:10,1')
            ->name('activity-logs.clear');
        Route::delete('/activity-logs/{log}', [ActivityLogController::class, 'destroy'])->name('activity-logs.destroy');

        // Mode maintenance
        Route::get('/maintenance', [MaintenanceController::class, 'edit'])->name('maintenance');
        Route::post('/maintenance', [MaintenanceController::class, 'update'])->name('maintenance.update');
        Route::get('/maintenance/preview', [MaintenanceController::class, 'preview'])->name('maintenance.preview');

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