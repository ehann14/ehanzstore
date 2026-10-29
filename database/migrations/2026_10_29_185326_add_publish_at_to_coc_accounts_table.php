<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal tayang akun.
     * Akun berstatus draft dengan publish_at terisi akan terbit otomatis saat waktunya tiba
     * (dicek di setiap request oleh middleware ExpireHotDeals, tanpa cron).
     * Kosong = tidak dijadwalkan.
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->timestamp('publish_at')->nullable()->after('publication_status');
            $table->index(['publication_status', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->dropIndex(['publication_status', 'publish_at']);
            $table->dropColumn('publish_at');
        });
    }
};