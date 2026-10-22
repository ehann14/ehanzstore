<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'link_type',
        'link_value',
        'link_url',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    /**
     * Banner yang tampil di beranda: aktif DAN sedang dalam masa tayang
     * (kosong = tanpa batas), urut sesuai sort_order lalu terbaru.
     * Banner terjadwal muncul dan hilang sendiri sesuai tanggal, tanpa cron.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                     })
                     ->orderBy('sort_order')
                     ->orderByDesc('id');
    }

    /**
     * Status tayang: hidden (dimatikan admin), scheduled (belum mulai),
     * ended (sudah berakhir), atau live (sedang tampil di beranda).
     */
    public function scheduleState(): string
    {
        if (! $this->is_active) {
            return 'hidden';
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return 'scheduled';
        }

        if ($this->ends_at && now()->gte($this->ends_at)) {
            return 'ended';
        }

        return 'live';
    }

    /** Ringkasan masa tayang untuk admin, mis. "25 Okt 2026, 08:00 - 31 Okt 2026, 23:59 WIB". */
    public function scheduleLabel(): ?string
    {
        if (! $this->starts_at && ! $this->ends_at) {
            return null;
        }

        $fmt = fn ($d) => $d->clone()->timezone(Sale::DISPLAY_TZ)->format('d M Y, H:i');

        if ($this->starts_at && $this->ends_at) {
            return $fmt($this->starts_at) . ' - ' . $fmt($this->ends_at) . ' WIB';
        }

        return $this->starts_at
            ? 'Mulai ' . $fmt($this->starts_at) . ' WIB'
            : 'Sampai ' . $fmt($this->ends_at) . ' WIB';
    }

    /** Nilai untuk input datetime-local (WIB). */
    public function scheduleInputValue(string $column): string
    {
        return $this->{$column}
            ? $this->{$column}->clone()->timezone(Sale::DISPLAY_TZ)->format('Y-m-d\\TH:i')
            : '';
    }

    public function getImageUrlAttribute(): string
    {
        return upload_url($this->image_path);
    }

    /**
     * Ubah input admin (tipe + nilai) menjadi URL tujuan akhir.
     */
    public static function buildLink(string $type, ?string $value): ?string
    {
        $value = trim((string) $value);

        if ($type === 'none' || $value === '') {
            return null;
        }

        if ($type === 'whatsapp') {
            $digits = preg_replace('/\D+/', '', $value);

            if (str_starts_with($digits, '0')) {
                $digits = '62' . substr($digits, 1);
            }

            return $digits ? 'https://wa.me/' . $digits : null;
        }

        if ($type === 'instagram') {
            // Terima "@user", "user", atau URL Instagram lengkap.
            $user = preg_replace(
                '#^https?://(www\.)?instagram\.com/#i',
                '',
                $value
            );

            $user = trim(ltrim($user, '@'), '/');
            $user = explode('?', $user)[0];

            return $user ? 'https://instagram.com/' . $user : null;
        }

        return $value; // custom: sudah divalidasi sebagai URL
    }
}