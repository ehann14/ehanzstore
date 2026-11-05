<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saklar booking (DP) per akun. ON (bawaan) = pembeli bisa booking, OFF = booking ditutup.
     *
     * Aman dijalankan ulang (kolom dicek dulu), berguna kalau SQL-nya sudah dijalankan manual.
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('coc_accounts', 'booking_enabled')) {
                $table->boolean('booking_enabled')->default(true)->after('sale_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('coc_accounts', 'booking_enabled')) {
                $table->dropColumn('booking_enabled');
            }
        });
    }
};