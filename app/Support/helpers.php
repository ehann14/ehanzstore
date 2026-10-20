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