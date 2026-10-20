<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data pembeli + masa garansi 7 hari untuk setiap transaksi penjualan.
     * Semua kolom nullable supaya transaksi lama (yang belum punya data pembeli)
     * tetap valid dan bisa dilengkapi belakangan lewat form "Data Pembeli".
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('buyer_name', 100)->nullable()->after('account_code_snapshot');
            $table->string('buyer_phone', 20)->nullable()->after('buyer_name');
            $table->string('payment_method', 50)->nullable()->after('payment_status');
            $table->timestamp('handover_at')->nullable()->after('sold_at');             // serah terima = awal garansi
            $table->timestamp('warranty_expires_at')->nullable()->after('handover_at'); // handover_at + 7 hari

            $table->index('warranty_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['warranty_expires_at']);
            $table->dropColumn([
                'buyer_name',
                'buyer_phone',
                'payment_method',
                'handover_at',
                'warranty_expires_at',
            ]);
        });
    }
};