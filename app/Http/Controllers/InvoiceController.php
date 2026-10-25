<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

/**
 * Invoice / bukti transaksi untuk pembeli (tanpa login).
 * Dibuka lewat link pribadi /invoice/{token} yang dikirim admin via WhatsApp.
 */
class InvoiceController extends Controller
{
    public function show(string $token)
    {
        $sale = $this->findSale($token);
        $sale->load('account.primaryImage');

        return response()
            ->view('public.invoice', [
                'sale'    => $sale,
                'account' => $sale->account,
                'phone'   => $this->maskPhone($sale->buyer_phone),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Cadangan jika pembeli kehilangan link: kode transaksi + 4 digit terakhir
     * nomor WhatsApp yang dipakai saat membeli.
     */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20'],
            'hp'   => ['required', 'digits:4'],
        ]);

        $code = strtoupper(preg_replace('/\s+/', '', $data['kode']));
        $sale = Sale::where('transaction_code', $code)
            ->where('transaction_status', 'completed')
            ->first();

        $ok = $sale
            && $sale->invoice_token
            && $sale->buyer_phone
            && hash_equals(substr($sale->buyer_phone, -4), $data['hp']);

        if (! $ok) {
            // Pesan sengaja dibuat sama agar kode transaksi tidak bisa ditebak.
            return redirect()
                ->route('warranty.check', ['kode' => $code])
                ->with('invoice_error', 'Kode transaksi atau 4 digit nomor WhatsApp tidak cocok.');
        }

        return redirect()->route('invoice.show', $sale->invoice_token);
    }

    /** Hanya transaksi selesai yang punya invoice. */
    private function findSale(string $token): Sale
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{20,64}$/', $token), 404);

        return Sale::where('invoice_token', $token)
            ->where('transaction_status', 'completed')
            ->firstOrFail();
    }

    /** 6281234567890 -> 62812****7890 */
    private function maskPhone(?string $phone): string
    {
        $phone = (string) $phone;

        if (strlen($phone) < 8) {
            return '-';
        }

        return substr($phone, 0, 5) . str_repeat('*', strlen($phone) - 9) . substr($phone, -4);
    }
}