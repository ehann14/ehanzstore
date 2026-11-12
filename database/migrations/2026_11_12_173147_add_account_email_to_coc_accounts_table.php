<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar email (Gmail) milik toko + penautan ke akun.
     *
     * - account_emails       : daftar email yang didaftarkan admin (khusus admin, tidak tampil ke pembeli).
     * - coc_accounts.account_email_id : email yang dipakai akun. UNIQUE, jadi satu email hanya bisa dipakai
     *   satu akun pada satu waktu. Akun terjual / dihapus melepas email-nya (kolom jadi NULL) lewat model.
     *
     * Kolom lama coc_accounts.account_email (teks bebas, migrasi sebelumnya) dipindahkan ke daftar ini
     * lalu dihapus. Aman dijalankan walau kolom lama tidak ada.
     */
    public function up(): void
    {
        if (! Schema::hasTable('account_emails')) {
            Schema::create('account_emails', function (Blueprint $table) {
                $table->id();
                $table->string('email', 191)->unique();
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('coc_accounts', 'account_email_id')) {
            Schema::table('coc_accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('account_email_id')->nullable()->after('email_change_status')->unique();
            });
        }

        if (Schema::hasColumn('coc_accounts', 'account_email')) {
            $rows = DB::table('coc_accounts')
                ->whereNotNull('account_email')
                ->where('account_email', '!=', '')
                ->orderBy('id')
                ->get(['id', 'account_email', 'sale_status', 'deleted_at', 'created_by']);

            $emailIds = [];
            $taken    = [];

            foreach ($rows as $row) {
                $email = strtolower(trim((string) $row->account_email));

                if ($email === '') {
                    continue;
                }

                if (! isset($emailIds[$email])) {
                    $emailIds[$email] = DB::table('account_emails')->where('email', $email)->value('id')
                        ?? DB::table('account_emails')->insertGetId([
                            'email'      => $email,
                            'created_by' => $row->created_by,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                // Hanya akun yang masih aktif (belum terjual / dihapus) yang memegang email.
                // Bila dua akun aktif memakai email yang sama, yang pertama memegang, sisanya dikosongkan.
                $active = $row->sale_status !== 'sold' && $row->deleted_at === null;

                if ($active && ! isset($taken[$emailIds[$email]])) {
                    DB::table('coc_accounts')->where('id', $row->id)->update(['account_email_id' => $emailIds[$email]]);
                    $taken[$emailIds[$email]] = true;
                }
            }

            Schema::table('coc_accounts', function (Blueprint $table) {
                $table->dropColumn('account_email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('coc_accounts', 'account_email_id')) {
            Schema::table('coc_accounts', function (Blueprint $table) {
                $table->dropUnique(['account_email_id']);
                $table->dropColumn('account_email_id');
            });
        }

        Schema::dropIfExists('account_emails');
    }
};