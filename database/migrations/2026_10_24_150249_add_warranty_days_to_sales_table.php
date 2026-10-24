<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Durasi garansi per transaksi (7 / 14 / 21 hari).
     * Default 7 supaya semua transaksi lama tetap sama seperti sebelumnya.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedTinyInteger('warranty_days')->default(7)->after('handover_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('warranty_days');
        });
    }
};