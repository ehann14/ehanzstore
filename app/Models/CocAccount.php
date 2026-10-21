<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CocAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_code', 'name', 'town_hall', 'account_level',
        'barbarian_king_level', 'archer_queen_level', 'minion_prince_level',
        'grand_warden_level', 'royal_champion_level', 'dragon_duke_level',
        'builder_count', 'gems', 'cn_information',
        'listing_type', 'clan_level', 'capital_hall_level', 'clan_members', 'clan_league',
        'protection_status', 'email_change_status', 'email_change_available_at', 'price', 'description',
        'publication_status', 'sale_status', 'created_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'email_change_available_at' => 'datetime',
    ];

    /** Liga Clan War League dari yang terendah ke tertinggi (urutan dipakai untuk filter "liga minimum"). */
    public const CLAN_LEAGUES = [
        'Unranked',
        'Bronze League III', 'Bronze League II', 'Bronze League I',
        'Silver League III', 'Silver League II', 'Silver League I',
        'Gold League III', 'Gold League II', 'Gold League I',
        'Crystal League III', 'Crystal League II', 'Crystal League I',
        'Master League III', 'Master League II', 'Master League I',
        'Champion League III', 'Champion League II', 'Champion League I',
    ];

    public const CLAN_MAX_MEMBERS = 50;

    public function isClan(): bool
    {
        return $this->listing_type === 'clan';
    }

    /** Label singkat untuk badge: "CLAN" atau "TH 16". */
    public function badgeLabel(): string
    {
        return $this->isClan() ? 'CLAN' : 'TH ' . $this->town_hall;
    }

    /** Kata benda untuk teks: "clan" atau "akun". */
    public function noun(): string
    {
        return $this->isClan() ? 'clan' : 'akun';
    }

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

    /**
     * Sisa hari sampai email akun bisa diganti (dibulatkan ke atas).
     * Null jika email sudah bisa diganti sekarang, tidak bisa diganti, atau ini clan.
     * Otomatis kembali null saat tanggalnya lewat, tanpa perlu cron.
     */
    public function emailChangeDaysLeft(): ?int
    {
        if ($this->isClan()
            || $this->email_change_status !== 'Changeable'
            || ! $this->email_change_available_at) {
            return null;
        }

        $seconds = $this->email_change_available_at->getTimestamp() - now()->getTimestamp();

        return $seconds > 0 ? (int) ceil($seconds / 86400) : null;
    }

    /** Teks status email untuk publik: "Bisa Ganti", "Bisa Ganti dalam 5 hari", "Tidak Bisa Ganti". */
    public function emailStatusLabel(): string
    {
        if ($this->email_change_status !== 'Changeable') {
            return 'Tidak Bisa Ganti';
        }

        $days = $this->emailChangeDaysLeft();

        return $days ? "Bisa Ganti dalam {$days} hari" : 'Bisa Ganti';
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