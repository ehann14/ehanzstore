<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboard;

// Public Routes
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/katalog', [PublicController::class, 'catalog'])->name('catalog');
Route::get('/akun/{account_code}', [PublicController::class, 'detail'])->name('account.detail');
Route::post('/track/event', [PublicController::class, 'trackEvent'])->name('track.event');
Route::get('/cara-membeli', [PublicController::class, 'howToBuy'])->name('how.to.buy');

// Auth Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes (Regular Admin + Super Admin)
Route::middleware(['auth', 'role:admin'])
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

        Route::post('accounts/{account}/images', [
            AccountController::class,
            'uploadImage'
        ])->name('accounts.images');

        // Banner beranda (slider)
        Route::resource('banners', BannerController::class)->except(['show']);
        Route::post('banners/{banner}/toggle', [BannerController::class, 'toggle'])
            ->name('banners.toggle');
    });

// Super Admin Routes
Route::middleware(['auth', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/dashboard', [SuperAdminDashboard::class, 'index'])
            ->name('dashboard');

        Route::get('/analytics', [SuperAdminDashboard::class, 'analytics'])
            ->name('analytics');

        Route::get('/sales', [SuperAdminDashboard::class, 'sales'])
            ->name('sales');

        Route::get('/admins', [SuperAdminDashboard::class, 'manageAdmins'])
            ->name('admins');

        // Konfirmasi penjualan oleh Super Admin
        Route::post('/sales/confirm', [
            SuperAdminDashboard::class,
            'confirmSale'
        ])->name('sales.confirm');

        // Tambahkan route CRUD admin dan settings di sini nanti
    });