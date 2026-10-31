<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Syarat wajib titip akun, bagian ke-2: bila proteksi akun ON, nomor HP proteksi
     * HARUS bisa diganti ke nomor pembeli. Admin wajib mengonfirmasi hal ini sebelum
     * akun titipan berproteksi bisa disimpan.
     *
     * Aman dijalankan ulang (kolom dicek dulu), berguna kalau SQL-nya sudah dijalankan manual.
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('coc_accounts', 'consignor_phone_ok')) {
                $table->boolean('consignor_phone_ok')->default(false)->after('consignor_email_ok');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('coc_accounts', 'consignor_phone_ok')) {
                $table->dropColumn('consignor_phone_ok');
            }
        });
    }
};