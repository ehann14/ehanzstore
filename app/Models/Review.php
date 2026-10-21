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
     *                  yang sudah hilang dari katalog setelah garansi berakhir.
     * - average      : rata-rata rating ulasan yang disetujui (null jika belum ada).
     * - reviews_count: jumlah ulasan yang disetujui.
     */
    public static function publicStats(): array
    {
        $reviewsCount = static::approved()->count();

        return [
            'sold_count'    => Sale::where('transaction_status', 'completed')->count(),
            'reviews_count' => $reviewsCount,
            'average'       => $reviewsCount > 0 ? round((float) static::approved()->avg('rating'), 1) : null,
        ];
    }
}