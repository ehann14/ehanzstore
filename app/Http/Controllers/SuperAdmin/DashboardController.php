<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CocAccount;
use App\Models\Sale;
use App\Models\User;
use App\Models\VisitorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Penting untuk Str::random()

class DashboardController extends Controller
{
    // ... method index, analytics, dll sebelumnya ...

    /**
     * Konfirmasi penjualan akun secara manual oleh Super Admin
     */
    public function confirmSale(Request $request) 
    {
        $request->validate([
            'account_id' => 'required|exists:coc_accounts,id',
            'price' => 'required|numeric',
            'notes' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request) {
            $account = CocAccount::findOrFail($request->account_id);
            
            // 1. Buat record penjualan
            Sale::create([
                'transaction_code' => 'TRX-' . strtoupper(Str::random(8)),
                'coc_account_id' => $account->id,
                'account_code_snapshot' => $account->account_code,
                'sale_price' => $request->price,
                'transaction_status' => 'completed',
                'payment_status' => 'paid',
                'processed_by' => Auth::id(),
                'sold_at' => now(),
                'notes' => $request->notes,
            ]);

            // 2. Update status akun
            $account->update([
                'sale_status' => 'sold',
                'publication_status' => 'draft' // Otomatis hide dari katalog
            ]);
        });

        return back()->with('success', 'Penjualan berhasil dikonfirmasi.');
    }
}