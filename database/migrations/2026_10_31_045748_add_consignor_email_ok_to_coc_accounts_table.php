<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Syarat wajib titip akun: email akun HARUS bisa diganti ke Gmail pembeli.
     * Admin wajib mengonfirmasi hal ini sebelum akun titipan bisa disimpan.
     *
     * Aman dijalankan ulang (kolom dicek dulu), berguna kalau SQL-nya sudah dijalankan manual.
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('coc_accounts', 'consignor_email_ok')) {
                $table->boolean('consignor_email_ok')->default(false)->after('commission_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('coc_accounts', 'consignor_email_ok')) {
                $table->dropColumn('consignor_email_ok');
            }
        });
    }
};