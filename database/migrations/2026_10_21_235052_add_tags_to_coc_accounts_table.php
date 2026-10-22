<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tag akun pemain (player_tag) dan tag clan (clan_tag), contoh: #P2LQ8Y0R.
     * Nullable supaya data lama tetap valid.
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->string('player_tag', 20)->nullable()->after('name');
            $table->string('clan_tag', 20)->nullable()->after('player_tag');
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->dropColumn(['player_tag', 'clan_tag']);
        });
    }
};