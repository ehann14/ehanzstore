<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) longgarkan kolom dulu agar nilai lama/baru sama-sama boleh
        DB::statement("ALTER TABLE coc_accounts MODIFY email_change_status VARCHAR(20) NOT NULL DEFAULT 'Unchangeable'");

        // 2) konversi data lama: 'Changed' -> Bisa Ganti, sisanya -> Tidak Bisa Ganti
        DB::table('coc_accounts')->where('email_change_status', 'Changed')->update(['email_change_status' => 'Changeable']);
        DB::table('coc_accounts')->whereNotIn('email_change_status', ['Changeable'])->update(['email_change_status' => 'Unchangeable']);

        // 3) kunci kembali menjadi enum dua pilihan
        DB::statement("ALTER TABLE coc_accounts MODIFY email_change_status ENUM('Changeable','Unchangeable') NOT NULL DEFAULT 'Unchangeable'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE coc_accounts MODIFY email_change_status VARCHAR(20) NOT NULL DEFAULT 'Unknown'");
        DB::table('coc_accounts')->where('email_change_status', 'Changeable')->update(['email_change_status' => 'Changed']);
        DB::table('coc_accounts')->where('email_change_status', 'Unchangeable')->update(['email_change_status' => 'Unknown']);
        DB::statement("ALTER TABLE coc_accounts MODIFY email_change_status ENUM('Original','Changed','Unknown') NOT NULL DEFAULT 'Unknown'");
    }
};