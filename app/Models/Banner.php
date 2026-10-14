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
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Banner yang tampil di beranda, urut sesuai sort_order lalu terbaru. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->orderBy('sort_order')
                     ->orderByDesc('id');
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
