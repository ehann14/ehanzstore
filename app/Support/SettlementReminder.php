<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Sale;
use Throwable;

/**
 * Pengingat pelunasan DP untuk admin.
 *
 * Booking yang DP-nya sudah diterima (status "active") harus lunas sebelum batas waktu,
 * dan batas itu bisa sangat dekat karena tidak boleh melewati waktu Gmail akun bisa diganti.
 * Kelas ini menandai booking yang batasnya besok (H-1) atau hari ini (Hari H), lalu
 * menyiapkan pesan WhatsApp siap kirim ke pembeli.
 *
 * Hari dihitung per tanggal kalender WIB, bukan per 24 jam.
 */
class SettlementReminder
{
    /**
     * Sisa hari kalender (WIB) sampai batas pelunasan: 1 = H-1 (besok), 0 = Hari H (hari ini).
     * Null bila booking ini tidak sedang menunggu pelunasan.
     */
    public static function daysLeft(Booking $booking): ?int
    {
        if ($booking->status !== Booking::ACTIVE
            || ! $booking->isPaid()
            || ! $booking->expires_at
            || ! $booking->isHolding()) {
            return null;
        }

        $today = now(Sale::DISPLAY_TZ)->startOfDay();
        $due   = $booking->expires_at->clone()->timezone(Sale::DISPLAY_TZ)->startOfDay();

        return (int) round($today->diffInDays($due, false));
    }

    /** Perlu diingatkan: batas pelunasan besok atau hari ini. */
    public static function isDue(Booking $booking): bool
    {
        $days = static::daysLeft($booking);

        return $days !== null && $days <= 1;
    }

    /** Teks penanda di daftar booking. */
    public static function label(int $daysLeft): string
    {
        return $daysLeft <= 0 ? 'Hari H: batas pelunasan hari ini' : 'H-1 batas pelunasan';
    }

    /** Kelas warna penanda: kuning = H-1, merah = Hari H. */
    public static function tone(int $daysLeft): string
    {
        return $daysLeft <= 0
            ? 'bg-red-500/20 text-red-200 border-red-500/50'
            : 'bg-amber-500/20 text-amber-200 border-amber-500/50';
    }

    /** Jumlah booking yang batas pelunasannya hari ini atau besok (WIB). */
    public static function dueSoonCount(): int
    {
        try {
            $end = now(Sale::DISPLAY_TZ)->addDay()->endOfDay()->timezone(config('app.timezone'));

            return Booking::where('status', Booking::ACTIVE)
                ->where('dp_state', Booking::DP_HELD)
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', $end)
                ->count();
        } catch (Throwable $e) {
            return 0; // tabel bookings belum dibuat: abaikan
        }
    }

    /** Pesan pengingat siap kirim ke pembeli. */
    public static function message(Booking $booking): string
    {
        $rp   = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $days = static::daysLeft($booking);
        $when = ($days !== null && $days <= 0) ? 'HARI INI' : 'BESOK';

        $text = 'Halo ' . $booking->buyer_name . ', ini EHANZSTORE. Pengingat: batas pelunasan booking '
            . $booking->booking_code . ' untuk akun ' . $booking->account_code_snapshot
            . ' jatuh ' . $when . ', ' . $booking->deadlineLabel() . ".\n\n"
            . 'DP ' . $rp($booking->dp_amount) . ' sudah kami terima. Sisa pelunasan: ' . $rp($booking->remaining()) . ".\n\n";

        // Batas pelunasan terpotong waktu Gmail akun bisa diganti: tidak bisa diperpanjang lagi.
        $limit = $booking->account ? Booking::emailLimit($booking->account) : null;

        if ($limit && $booking->expires_at->gte($limit->clone()->subMinute())) {
            $text .= "Batas ini mengikuti waktu Gmail akun bisa diganti, jadi tidak bisa diperpanjang.\n\n";
        }

        $text .= "Mohon lunasi sebelum batas waktu ya. Kalau lewat, akun dibuka kembali untuk dijual dan DP hangus sesuai syarat & ketentuan.\n\n"
            . 'Detail booking: ' . $booking->publicUrl();

        return $text;
    }

    /** Link WhatsApp ke pembeli dengan pesan pengingat sudah terisi. */
    public static function whatsappUrl(Booking $booking): string
    {
        return $booking->buyerWhatsappUrl(static::message($booking));
    }
}