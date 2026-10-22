<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Masa berlaku Hot Deal / diskon.
     * Saat waktunya lewat, diskon (harga coret) dan label Hot Deal hilang otomatis,
     * harga kembali ke harga normal. Kosong = promo tanpa batas waktu.
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->timestamp('hot_deal_ends_at')->nullable()->after('is_hot_deal');
            $table->index('hot_deal_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->dropIndex(['hot_deal_ends_at']);
            $table->dropColumn('hot_deal_ends_at');
        });
    }
};