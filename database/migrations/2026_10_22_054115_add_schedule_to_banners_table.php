<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Kosong = tanpa batas. Tampil hanya bila sekarang berada di antara keduanya.
            $table->dateTime('starts_at')->nullable()->after('sort_order');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropIndex(['starts_at', 'ends_at']);
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};