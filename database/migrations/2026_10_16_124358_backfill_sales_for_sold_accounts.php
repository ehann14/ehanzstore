<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Buat transaksi untuk akun yang sudah berstatus "sold"
     * tetapi belum punya transaksi selesai (data lama yang tidak tercatat).
     * Aman dijalankan berulang: akun yang sudah punya transaksi dilewati.
     */
    public function up(): void
    {
        $fallbackUser = DB::table('users')->where('role', 'super_admin')->value('id');

        $accounts = DB::table('coc_accounts')
            ->whereNull('deleted_at')
            ->where('sale_status', 'sold')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('sales')
                  ->whereColumn('sales.coc_account_id', 'coc_accounts.id')
                  ->where('sales.transaction_status', 'completed');
            })
            ->get();

        foreach ($accounts as $account) {
            do {
                $code = 'TRX-' . strtoupper(Str::random(8));
            } while (DB::table('sales')->where('transaction_code', $code)->exists());

            DB::table('sales')->insert([
                'transaction_code'      => $code,
                'coc_account_id'        => $account->id,
                'account_code_snapshot' => $account->account_code,
                'sale_price'            => $account->price,
                'transaction_status'    => 'completed',
                'payment_status'        => 'paid',
                'processed_by'          => $account->created_by ?? $fallbackUser,
                'sold_at'               => $account->updated_at ?? now(),
                'notes'                 => 'Dicatat otomatis dari akun yang sudah berstatus terjual.',
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('sales')
            ->where('notes', 'Dicatat otomatis dari akun yang sudah berstatus terjual.')
            ->delete();
    }
};