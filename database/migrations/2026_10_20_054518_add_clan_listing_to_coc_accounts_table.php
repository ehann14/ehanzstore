<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            // 'account' = akun pemain, 'clan' = clan yang dijual
            $table->enum('listing_type', ['account', 'clan'])->default('account')->after('account_code');
            $table->unsignedSmallInteger('clan_level')->default(0)->after('listing_type');
            $table->unsignedSmallInteger('capital_hall_level')->default(0)->after('clan_level');
            $table->unsignedSmallInteger('clan_members')->default(0)->after('capital_hall_level');
            $table->string('clan_league', 40)->nullable()->after('clan_members');

            $table->index('listing_type');
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->dropIndex(['listing_type']);
            $table->dropColumn(['listing_type', 'clan_level', 'capital_hall_level', 'clan_members', 'clan_league']);
        });
    }
};