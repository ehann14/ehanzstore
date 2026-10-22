<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Harga coret / diskon dan label "Hot Deal".
     *  - price          : harga jual sekarang (setelah diskon), tetap dipakai untuk transaksi & laporan
     *  - original_price : harga normal sebelum diskon (opsional). Tampil sebagai harga coret
     *                     bila nilainya lebih besar dari price.
     *  - is_hot_deal    : penanda manual dari admin untuk menampilkan label "Hot Deal".
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->decimal('original_price', 12, 2)->nullable()->after('price');
            $table->boolean('is_hot_deal')->default(false)->after('original_price');
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'is_hot_deal']);
        });
    }
};