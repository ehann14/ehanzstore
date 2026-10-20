<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CocAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_code', 'name', 'town_hall', 'account_level',
        'barbarian_king_level', 'archer_queen_level', 'grand_warden_level',
        'royal_champion_level', 'builder_count', 'gems', 'cn_information',
        'protection_status', 'email_change_status', 'price', 'description',
        'publication_status', 'sale_status', 'created_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Sinkronkan tabel transaksi dengan status penjualan akun.
        static::updated(function (CocAccount $account) {
            if (! $account->wasChanged('sale_status')) {
                return;
            }

            if ($account->sale_status === 'sold') {
                // Akun ditandai terjual -> pastikan ada transaksi selesai
                Sale::recordForAccount($account);
            } elseif ($account->getOriginal('sale_status') === 'sold') {
                // Status terjual dibatalkan -> transaksi lama dibatalkan
                // agar pendapatan tidak dihitung dua kali
                $account->sales()
                    ->where('transaction_status', 'completed')
                    ->update([
                        'transaction_status' => 'cancelled',
                        'payment_status'     => 'unpaid',
                    ]);
            }
        });
    }

    public function images()
    {
        return $this->hasMany(CocAccountImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(CocAccountImage::class)->where('is_primary', true);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /** Transaksi selesai terakhir (berisi data pembeli & garansi). */
    public function latestCompletedSale()
    {
        return $this->hasOne(Sale::class)
            ->ofMany(['id' => 'max'], fn ($q) => $q->where('transaction_status', 'completed'));
    }

    /**
     * Akun yang tampil di katalog publik:
     *  - terbit dan masih tersedia, ATAU
     *  - terbit, sudah terjual, dan masa garansi transaksinya belum berakhir.
     * Setelah garansi berakhir akun otomatis hilang dari katalog (tanpa perlu cron).
     */
    public function scopeListed($query)
    {
        return $query->where('publication_status', 'published')
            ->where(function ($q) {
                $q->where('sale_status', 'available')
                  ->orWhere(function ($sold) {
                      $sold->where('sale_status', 'sold')
                           ->whereHas('sales', function ($s) {
                               $s->where('transaction_status', 'completed')
                                 ->where('warranty_expires_at', '>', now());
                           });
                  });
            });
    }

    // Scope untuk publik (hanya yang masih bisa dibeli)
    public function scopeAvailable($query)
    {
        return $query->where('publication_status', 'published')
                     ->where('sale_status', 'available');
    }
}