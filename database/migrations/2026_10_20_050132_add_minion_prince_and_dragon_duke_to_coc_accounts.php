<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->integer('minion_prince_level')->default(0)->after('archer_queen_level');
            $table->integer('dragon_duke_level')->default(0)->after('royal_champion_level');
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->dropColumn(['minion_prince_level', 'dragon_duke_level']);
        });
    }
};