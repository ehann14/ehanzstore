<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Email (Gmail) yang dipakai akun game. HANYA untuk admin: tidak pernah ditampilkan ke pembeli.
     * Dicek dulu dengan hasColumn supaya aman bila kolom sudah dibuat manual lewat phpMyAdmin.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('coc_accounts', 'account_email')) {
            Schema::table('coc_accounts', function (Blueprint $table) {
                $table->string('account_email', 191)->nullable()->after('email_change_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('coc_accounts', 'account_email')) {
            Schema::table('coc_accounts', function (Blueprint $table) {
                $table->dropColumn('account_email');
            });
        }
    }
};