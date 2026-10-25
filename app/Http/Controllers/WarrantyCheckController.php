<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarrantyCheckController extends Controller
{
    /**
     * Halaman publik cek sisa garansi lewat kode transaksi (mis. TRX-AB12CD34).
     *
     * Variabel untuk view public.warranty-check:
     *   $raw      input mentah dari pengguna
     *   $error    pesan validasi (atau null)
     *   $state    null | not_found | not_applicable | not_started | active | expired
     *   $sale     transaksi yang ditemukan (atau null)
     *   $buyer    nama pembeli yang sudah disamarkan
     *   $progress ['remaining' => persen sisa garansi 0-100]
     */
    public function index(Request $request)
    {
        $raw      = trim((string) $request->query('kode', ''));
        $error    = null;
        $state    = null;
        $sale     = null;
        $buyer    = null;
        $progress = ['remaining' => 0];

        if ($raw !== '') {
            $code = strtoupper(preg_replace('/\s+/', '', $raw));

            // Izinkan pengguna mengetik tanpa "TRX-" (mis. AB12CD34).
            if (preg_match('/^[A-Z0-9]{8}$/', $code)) {
                $code = 'TRX-' . $code;
            }

            if (strlen($code) > 20 || ! preg_match('/^[A-Z0-9\-]+$/', $code)) {
                $error = 'Format kode tidak valid. Contoh: TRX-AB12CD34';
                $raw   = Str::limit($raw, 20, '');
            } else {
                $sale = Sale::with(['account.primaryImage'])
                    ->where('transaction_code', $code)
                    ->first();

                if (! $sale) {
                    $state = 'not_found';
                } elseif ($sale->transaction_status !== 'completed') {
                    $state = 'not_applicable';
                } elseif (! $sale->warranty_expires_at || ! $sale->handover_at) {
                    $state = 'not_started';
                } else {
                    $state = $sale->warrantyIsActive() ? 'active' : 'expired';
                    $buyer = $this->maskName($sale->buyer_name);

                    $total = max(1, $sale->warrantyDays() * 86400);
                    $left  = max(0, $sale->warranty_expires_at->getTimestamp() - now()->getTimestamp());

                    $progress['remaining'] = (int) min(100, max(0, round($left / $total * 100)));
                }
            }
        }

        return view('public.warranty-check', compact(
            'raw', 'error', 'state', 'sale', 'buyer', 'progress'
        ));
    }

    /**
     * Samarkan nama pembeli supaya tidak bocor ke publik: "Budi Santoso" -> "Bu** Sa*****".
     */
    private function maskName(?string $name): ?string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $words = preg_split('/\s+/', $name);

        return implode(' ', array_map(function (string $word) {
            $len = mb_strlen($word);

            if ($len <= 2) {
                return mb_substr($word, 0, 1) . str_repeat('*', max(0, $len - 1));
            }

            return mb_substr($word, 0, 2) . str_repeat('*', $len - 2);
        }, $words));
    }
}