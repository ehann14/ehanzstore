<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal email akun baru bisa diganti (untuk stok baru: "bisa ganti dalam N hari").
     * Null = email sudah bisa diganti sekarang (atau memang tidak bisa diganti).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('coc_accounts', 'email_change_available_at')) {
            Schema::table('coc_accounts', function (Blueprint $table) {
                $table->timestamp('email_change_available_at')->nullable()->after('email_change_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('coc_accounts', 'email_change_available_at')) {
            Schema::table('coc_accounts', function (Blueprint $table) {
                $table->dropColumn('email_change_available_at');
            });
        }
    }
};