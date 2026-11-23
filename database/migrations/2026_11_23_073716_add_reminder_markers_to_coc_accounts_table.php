<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda notifikasi sudah terkirim (supaya tidak dobel):
     *  - publish_reminded_for      : nilai publish_at yang sudah diingatkan (ss ulang spek)
     *  - email_ready_notified_for  : nilai email_change_available_at yang sudah diberi tahu "Gmail bisa diganti"
     * Disimpan sebagai tanggal yang diingatkan, bukan true/false, jadi kalau jadwal diubah
     * pengingat otomatis berlaku lagi untuk jadwal baru.
     */
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('coc_accounts', 'publish_reminded_for')) {
                $table->timestamp('publish_reminded_for')->nullable();
            }
            if (! Schema::hasColumn('coc_accounts', 'email_ready_notified_for')) {
                $table->timestamp('email_ready_notified_for')->nullable();
            }
        });

        // Akun yang Gmail-nya sudah lewat masa tunggu sebelum fitur ini ada: anggap sudah diberi tahu,
        // supaya admin tidak dibanjiri notifikasi lama saat pertama kali jalan.
        DB::table('coc_accounts')
            ->whereNotNull('email_change_available_at')
            ->where('email_change_available_at', '<=', now())
            ->update(['email_ready_notified_for' => DB::raw('email_change_available_at')]);
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->dropColumn(['publish_reminded_for', 'email_ready_notified_for']);
        });
    }
};