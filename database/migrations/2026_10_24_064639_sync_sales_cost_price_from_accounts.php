<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbaiki data lama: transaksi selesai yang modalnya masih 0 padahal modal akunnya sudah diisi.
     * Hanya menyentuh baris yang modalnya 0, jadi modal yang sengaja diubah di transaksi tidak ditimpa.
     */
    public function up(): void
    {
        DB::statement(
            "UPDATE sales
             SET cost_price = (SELECT cost_price FROM coc_accounts WHERE coc_accounts.id = sales.coc_account_id)
             WHERE transaction_status = 'completed'
               AND cost_price = 0
               AND (SELECT cost_price FROM coc_accounts WHERE coc_accounts.id = sales.coc_account_id) > 0"
        );
    }

    public function down(): void
    {
        // Perbaikan data, tidak perlu dibatalkan.
    }
};