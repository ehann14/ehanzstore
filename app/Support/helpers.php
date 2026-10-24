<?php

if (! function_exists('upload_url')) {
    /**
     * URL publik untuk file di folder uploads (public_html/uploads/...).
     *
     * Menerima path dari database seperti "accounts/xxx.jpg" / "banners/xxx.png",
     * termasuk data lama berawalan "storage/", "public/" atau "uploads/".
     * Hasilnya "/uploads/accounts/xxx.jpg" (relatif ke domain, jadi aman dari
     * masalah http/https). Gunakan $absolute = true untuk URL lengkap, misalnya
     * untuk meta og:image.
     */
    function upload_url(?string $path, bool $absolute = false): string
    {
        $raw = trim((string) $path);

        if ($raw === '') {
            return '';
        }

        // URL eksternal dipakai apa adanya
        if (preg_match('#^(https?:)?//#i', $raw)) {
            return $raw;
        }

        $relative = \App\Support\Uploads::normalize($raw);

        if ($relative === '') {
            return '';
        }

        $url = '/uploads/' . $relative;

        return $absolute ? url($url) : $url;
    }
}

if (! function_exists('account_wa_url')) {
    /**
     * Link WhatsApp "Beli via WhatsApp" untuk satu akun/clan.
     *
     * Pesan terisi otomatis: nama, kode, jenis (TH / level clan), harga, dan link halaman akun,
     * jadi admin langsung tahu akun mana yang dimaksud tanpa pembeli mengetik ulang.
     * $number = nomor admin dari pengaturan toko ($storeWa di view); bila kosong dipakai nomor bawaan.
     */
    function account_wa_url(\App\Models\CocAccount $account, ?string $number = null): string
    {
        $number = preg_replace('/\D+/', '', (string) $number) ?: '6282130559126';

        $jenis = $account->isClan()
            ? 'Clan Lv. ' . $account->clan_level . ($account->clan_league ? ' - ' . $account->clan_league : '')
            : 'TH ' . $account->town_hall;

        $lines = [
            'Halo Admin EHANZSTORE, saya mau tanya/beli ' . $account->noun() . ' ini:',
            '',
            '*' . $account->name . '*',
            'Kode: ' . $account->account_code,
            'Jenis: ' . $jenis,
            'Harga: Rp ' . number_format((float) $account->price, 0, ',', '.'),
            'Link: ' . route('account.detail', $account->account_code),
            '',
            'Apakah masih tersedia?',
        ];

        return 'https://wa.me/' . $number . '?text=' . rawurlencode(implode("\n", $lines));
    }
}