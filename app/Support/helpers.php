<?php

if (! function_exists('upload_url')) {
    /**
     * URL publik untuk file di folder uploads (public_html/uploads/...).
     * Menerima "accounts/xxx.jpg" / "banners/xxx.png", termasuk data lama
     * berawalan "storage/", "public/" atau "uploads/".
     */
    function upload_url(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $path = preg_replace('#^(public/)?(storage/|uploads/)#i', '', $path);
        $path = preg_replace('#^public/#i', '', $path);

        return '/uploads/' . $path;
    }
}