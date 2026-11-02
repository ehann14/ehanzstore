<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link titip untuk penjual (tanpa login).
     * Admin membuat akun draft hanya dengan nama, lalu membagikan link berisi token sekali pakai.
     * Penjual mengisi data & foto lewat link itu; setelah dikirim, token dihapus sehingga link mati.
     *
     * submission_status: pending (menunggu diisi penjual) | submitted (sudah diisi, menunggu ditinjau admin)
     *
     * Aman dijalankan ulang: tiap kolom dicek dulu (berguna kalau SQL-nya sudah dijalankan manual).
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('coc_accounts', 'submission_token')) {
                $table->string('submission_token', 64)->nullable()->unique();
            }
            if (! Schema::hasColumn('coc_accounts', 'submission_status')) {
                $table->string('submission_status', 12)->nullable();
            }
            if (! Schema::hasColumn('coc_accounts', 'submission_expires_at')) {
                $table->timestamp('submission_expires_at')->nullable();
            }
            if (! Schema::hasColumn('coc_accounts', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            foreach (['submission_token', 'submission_status', 'submission_expires_at', 'submitted_at'] as $col) {
                if (Schema::hasColumn('coc_accounts', $col)) {
                    if ($col === 'submission_token') {
                        $table->dropUnique(['submission_token']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};