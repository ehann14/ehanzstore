<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'visitor_events_created_at_index';

    /**
     * Mempercepat penghapusan data lama (visitors:prune) dan statistik dashboard
     * yang memfilter hanya berdasarkan rentang waktu.
     */
    public function up(): void
    {
        if (Schema::hasIndex('visitor_events', self::INDEX)) {
            return;
        }

        Schema::table('visitor_events', function (Blueprint $table) {
            $table->index('created_at', self::INDEX);
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