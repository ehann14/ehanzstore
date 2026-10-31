<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biaya tambahan Garansi Plus (21 hari) per transaksi, sudah termasuk di sale_price.
     * Dicatat terpisah supaya komisi titip jual tidak ikut menghitung biaya ini.
     * Aman dijalankan ulang (kolom dicek dulu).
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'warranty_fee')) {
                $table->unsignedInteger('warranty_fee')->default(0)->after('warranty_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'warranty_fee')) {
                $table->dropColumn('warranty_fee');
            }
        });
    }
};