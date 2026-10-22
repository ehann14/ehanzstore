<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'sale_id', 'coc_account_id', 'reviewer_name', 'rating', 'comment',
        'status', 'moderated_by', 'moderated_at',
    ];

    protected $casts = [
        'rating'       => 'integer',
        'moderated_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function account()
    {
        return $this->belongsTo(CocAccount::class, 'coc_account_id')->withTrashed();
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Ringkasan bukti sosial untuk beranda.
     * - sold_count   : seluruh transaksi selesai (tabel sales), tidak terpengaruh akun
     * - sold_accounts / sold_clans : sold_count dipisah per jenis (akun pemain / clan)
     *                  yang sudah hilang dari katalog setelah garansi berakhir.
     * - average      : rata-rata rating ulasan yang disetujui (null jika belum ada).
     * - reviews_count: jumlah ulasan yang disetujui.
     */
    public static function publicStats(): array
    {
        $reviewsCount = static::approved()->count();

        $soldCount = Sale::where('transaction_status', 'completed')->count();

        // Pisahkan terjual per jenis. Join tanpa filter soft delete supaya
        // clan/akun yang sudah dihapus tetap terhitung sebagai terjual.
        $soldClans = Sale::where('sales.transaction_status', 'completed')
            ->join('coc_accounts', 'coc_accounts.id', '=', 'sales.coc_account_id')
            ->where('coc_accounts.listing_type', 'clan')
            ->count();

        return [
            'sold_count'    => $soldCount,
            'sold_accounts' => max(0, $soldCount - $soldClans),
            'sold_clans'    => $soldClans,
            'reviews_count' => $reviewsCount,
            'average'       => $reviewsCount > 0 ? round((float) static::approved()->avg('rating'), 1) : null,
        ];
    }
}