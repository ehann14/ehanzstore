<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

/**
 * Cek garansi untuk pembeli.
 * Pembeli memasukkan kode transaksi (TRX-XXXXXXXX) lalu melihat sisa garansi 7 harinya.
 * Semua data diambil dari tabel sales. Tidak perlu login.
 */
class WarrantyCheckController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->query('kode');
        $raw   = is_string($input) ? trim($input) : '';

        $sale     = null;
        $state    = null;
        $error    = null;
        $progress = null;
        $buyer    = null;

        if ($raw !== '') {
            $code = $this->normalizeCode($raw);

            if ($code === null) {
                $error = 'Format kode transaksi tidak valid. Contoh: TRX-AB12CD34.';
            } else {
                $sale  = Sale::with('account')->where('transaction_code', $code)->first();
                $state = $this->stateFor($sale);

                if (in_array($state, ['active', 'expired'], true)) {
                    $progress = $this->progress($sale);
                    $buyer    = $this->maskName($sale->buyer_name);
                }
            }
        }

        return view('public.warranty-check', [
            'raw'      => $raw,
            'sale'     => $sale,
            'state'    => $state,
            'error'    => $error,
            'progress' => $progress,
            'buyer'    => $buyer,
        ]);
    }

    /**
     * Rapikan input: "trx-ab12cd34", "TRX AB12CD34", atau "ab12cd34" -> "TRX-AB12CD34".
     * Mengembalikan null jika bentuknya bukan 8 karakter huruf/angka.
     */
    private function normalizeCode(string $raw): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $raw));

        if (str_starts_with($clean, 'TRX')) {
            $clean = substr($clean, 3);
        }

        return preg_match('/^[A-Z0-9]{8}$/', $clean) ? 'TRX-' . $clean : null;
    }

    /**
     * not_found      : kode tidak ada
     * not_applicable : transaksi belum selesai / dibatalkan
     * not_started    : transaksi selesai tapi data serah terima (awal garansi) belum diisi admin
     * active         : garansi masih berjalan
     * expired        : garansi sudah berakhir
     */
    private function stateFor(?Sale $sale): string
    {
        if (! $sale) {
            return 'not_found';
        }

        if ($sale->transaction_status !== 'completed') {
            return 'not_applicable';
        }

        if (! $sale->warranty_expires_at) {
            return 'not_started';
        }

        return $sale->warrantyIsActive() ? 'active' : 'expired';
    }

    /** Persentase garansi yang tersisa untuk progress bar. */
    private function progress(Sale $sale): array
    {
        $end   = $sale->warranty_expires_at->getTimestamp();
        $start = ($sale->handover_at ?? $sale->warranty_expires_at->clone()->subDays(Sale::WARRANTY_DAYS))->getTimestamp();

        $total = max(1, $end - $start);
        $left  = max(0, $end - now()->getTimestamp());

        $remaining = (int) round(min(100, max(0, $left / $total * 100)));

        return ['remaining' => $remaining, 'used' => 100 - $remaining];
    }

    /** "syamjaya" -> "s******a": cukup untuk meyakinkan pembeli tanpa membuka nama lengkap. */
    private function maskName(?string $name): ?string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return collect(preg_split('/\s+/u', $name))
            ->map(function (string $word) {
                $len = mb_strlen($word);

                if ($len <= 2) {
                    return mb_substr($word, 0, 1) . '*';
                }

                return mb_substr($word, 0, 1) . str_repeat('*', min($len - 2, 6)) . mb_substr($word, -1);
            })
            ->implode(' ');
    }
}