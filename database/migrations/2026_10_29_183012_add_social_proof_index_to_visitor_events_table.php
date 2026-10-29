<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'visitor_events_account_event_time_index';

    /**
     * Mempercepat penghitungan social proof di halaman detail akun
     * (filter per akun + jenis event + rentang waktu).
     */
    public function up(): void
    {
        if (Schema::hasIndex('visitor_events', self::INDEX)) {
            return;
        }

        Schema::table('visitor_events', function (Blueprint $table) {
            $table->index(['coc_account_id', 'event_type', 'created_at'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasIndex('visitor_events', self::INDEX)) {
            return;
        }

        Schema::table('visitor_events', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};