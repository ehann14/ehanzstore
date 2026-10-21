<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Sale;
use Illuminate\Http\Request;

/**
 * Form ulasan untuk pembeli. Diakses lewat link pribadi /ulasan/{token}
 * yang dikirim admin setelah akun terjual. Tidak perlu login.
 */
class ReviewController extends Controller
{
    public function show(string $token)
    {
        $sale = $this->findSale($token);
        $sale->load(['account.primaryImage', 'review']);

        return view('public.review', [
            'sale'    => $sale,
            'account' => $sale->account,
            'review'  => $sale->review,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $sale = $this->findSale($token);

        // Jebakan bot: kolom ini disembunyikan dari manusia, jadi harus kosong.
        if ($request->filled('website')) {
            return redirect()->route('review.form', $token);
        }

        if ($sale->review()->exists()) {
            return redirect()->route('review.form', $token);
        }

        $data = $request->validate([
            'reviewer_name' => ['required', 'string', 'min:2', 'max:60'],
            'rating'        => ['required', 'integer', 'between:1,5'],
            'comment'       => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'reviewer_name.required' => 'Nama wajib diisi.',
            'reviewer_name.min'      => 'Nama minimal 2 karakter.',
            'rating.required'        => 'Pilih jumlah bintang.',
            'rating.between'         => 'Bintang harus antara 1 sampai 5.',
            'comment.required'       => 'Ulasan wajib diisi.',
            'comment.min'            => 'Ulasan minimal 10 karakter.',
            'comment.max'            => 'Ulasan maksimal 500 karakter.',
        ]);

        Review::create([
            'sale_id'        => $sale->id,
            'coc_account_id' => $sale->coc_account_id,
            'reviewer_name'  => trim(strip_tags($data['reviewer_name'])),
            'rating'         => $data['rating'],
            'comment'        => trim(strip_tags($data['comment'])),
            'status'         => 'pending',
        ]);

        return redirect()->route('review.form', $token)->with('review_sent', true);
    }

    /** Hanya transaksi selesai yang boleh diulas. */
    private function findSale(string $token): Sale
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{20,64}$/', $token), 404);

        return Sale::where('review_token', $token)
            ->where('transaction_status', 'completed')
            ->firstOrFail();
    }
}