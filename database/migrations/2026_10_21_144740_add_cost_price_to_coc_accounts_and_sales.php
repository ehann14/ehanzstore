<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Harga modal (cost price) untuk menghitung laba.
     *  - coc_accounts.cost_price : modal per akun/clan (diisi admin di form akun)
     *  - sales.cost_price        : snapshot modal saat transaksi, supaya laba transaksi
     *                              lama tidak berubah kalau modal akun diedit belakangan.
     * Default 0 agar data lama tetap valid (laba lama = harga jual sampai modalnya diisi).
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->default(0)->after('price');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->default(0)->after('sale_price');
        });

        // Transaksi lama: salin modal dari akunnya (umumnya masih 0, aman).
        DB::statement('UPDATE sales SET cost_price = (SELECT cost_price FROM coc_accounts WHERE coc_accounts.id = sales.coc_account_id)');
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });

        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};