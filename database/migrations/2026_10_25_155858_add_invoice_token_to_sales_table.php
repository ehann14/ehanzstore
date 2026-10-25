<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales', 'invoice_token')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('invoice_token', 64)->nullable()->unique()->after('review_token');
            });
        }

        // Transaksi lama ikut dapat token supaya invoice-nya bisa dibuka.
        DB::table('sales')->whereNull('invoice_token')->orderBy('id')->each(function ($sale) {
            DB::table('sales')->where('id', $sale->id)->update(['invoice_token' => Str::random(40)]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'invoice_token')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropUnique(['invoice_token']);
                $table->dropColumn('invoice_token');
            });
        }
    }
};