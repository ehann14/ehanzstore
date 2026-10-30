<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mode titip jual (konsinyasi).
     *
     *  - coc_accounts : tanda titip jual, data pemilik akun, dan aturan komisi toko.
     *  - sales        : snapshot saat terjual (pemilik, komisi, jumlah yang harus disetor ke pemilik)
     *                   + kapan setoran ke pemilik dilunasi.
     *
     * Untuk akun titipan, harga modal (cost_price) = bagian pemilik (harga jual - komisi),
     * jadi laba = komisi dan semua laporan yang sudah ada tetap benar tanpa diubah.
     *
     * Aman dijalankan ulang: tiap kolom dicek dulu (berguna kalau SQL-nya sudah dijalankan manual).
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('coc_accounts', 'is_consignment')) {
                $table->boolean('is_consignment')->default(false)->after('cost_price');
            }
            if (! Schema::hasColumn('coc_accounts', 'consignor_name')) {
                $table->string('consignor_name', 100)->nullable()->after('is_consignment');
            }
            if (! Schema::hasColumn('coc_accounts', 'consignor_phone')) {
                $table->string('consignor_phone', 20)->nullable()->after('consignor_name');
            }
            if (! Schema::hasColumn('coc_accounts', 'commission_type')) {
                $table->string('commission_type', 10)->default('percent')->after('consignor_phone');
            }
            if (! Schema::hasColumn('coc_accounts', 'commission_value')) {
                $table->decimal('commission_value', 12, 2)->default(0)->after('commission_type');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'is_consignment')) {
                $table->boolean('is_consignment')->default(false)->after('cost_price');
            }
            if (! Schema::hasColumn('sales', 'consignor_name')) {
                $table->string('consignor_name', 100)->nullable()->after('is_consignment');
            }
            if (! Schema::hasColumn('sales', 'consignor_phone')) {
                $table->string('consignor_phone', 20)->nullable()->after('consignor_name');
            }
            if (! Schema::hasColumn('sales', 'commission_amount')) {
                $table->decimal('commission_amount', 12, 2)->default(0)->after('consignor_phone');
            }
            if (! Schema::hasColumn('sales', 'consignor_payout')) {
                $table->decimal('consignor_payout', 12, 2)->default(0)->after('commission_amount');
            }
            if (! Schema::hasColumn('sales', 'consignor_paid_at')) {
                $table->timestamp('consignor_paid_at')->nullable()->after('consignor_payout');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            foreach (['consignor_paid_at', 'consignor_payout', 'commission_amount', 'consignor_phone', 'consignor_name', 'is_consignment'] as $col) {
                if (Schema::hasColumn('sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('coc_accounts', function (Blueprint $table) {
            foreach (['commission_value', 'commission_type', 'consignor_phone', 'consignor_name', 'is_consignment'] as $col) {
                if (Schema::hasColumn('coc_accounts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};