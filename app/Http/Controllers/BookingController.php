<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Sale;
use App\Support\AdminNotifier;
use App\Support\PaymentSettings;
use App\Support\SpamGuard;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /** Halaman syarat & ketentuan DP / booking. */
    public function terms()
    {
        return view('public.booking-terms');
    }

    /** Pembeli membuat booking dari halaman detail akun. */
    public function store(Request $request, string $account_code)
    {
        // Jebakan bot: kolom ini disembunyikan dari manusia, jadi harus kosong.
        // Bot dibuat "mengira berhasil": tidak ada booking, tidak ada notifikasi ke admin.
        if (SpamGuard::honeypotTripped($request)) {
            SpamGuard::logBot($request, 'booking');

            return redirect()->route('account.detail', $account_code);
        }

        $data = $request->validateWithBag('booking', [
            'buyer_name'  => ['required', 'string', 'min:2', 'max:100'],
            'buyer_phone' => [
                'required', 'string', 'max:25',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $phone = Sale::normalizePhone($value);

                    if (! $phone || ! preg_match('/^628[0-9]{8,12}$/', $phone)) {
                        $fail('Nomor WhatsApp tidak valid. Contoh: 0812-3456-7890.');
                    }
                },
            ],
            'terms'       => ['accepted'],
        ], [
            'buyer_name.required'  => 'Nama wajib diisi.',
            'buyer_name.min'       => 'Nama minimal 2 karakter.',
            'buyer_phone.required' => 'Nomor WhatsApp wajib diisi.',
            'terms.accepted'       => 'Kamu harus menyetujui syarat & ketentuan booking DP.',
        ]);

        // Batas booking per IP (per jam & per hari). Salah ketik atau akun yang sudah laku tidak dihitung.
        if ($wait = SpamGuard::blockedFor($request, 'booking')) {
            return redirect()
                ->route('account.detail', $account_code)
                ->withInput($request->only('buyer_name', 'buyer_phone'))
                ->withErrors(['booking' => 'Terlalu banyak booking dari jaringan ini. Coba lagi dalam ' . SpamGuard::waitText($wait) . ', atau hubungi admin lewat WhatsApp.'], 'booking');
        }

        [$booking, $error] = Booking::place(
            $account_code,
            trim($data['buyer_name']),
            Sale::normalizePhone($data['buyer_phone']),
            $request->ip()
        );

        if (! $booking) {
            return redirect()
                ->route('account.detail', $account_code)
                ->withInput($request->only('buyer_name', 'buyer_phone'))
                ->withErrors(['booking' => $error], 'booking');
        }

        SpamGuard::hit($request, 'booking');

        // Beri tahu admin lewat lonceng notifikasi
        AdminNotifier::newBooking($booking);

        return redirect()->route('booking.show', $booking->token)->with('booking_created', true);
    }

    /** Halaman booking pribadi pembeli (diakses lewat link berisi token). */
    public function show(string $token)
    {
        $booking = Booking::where('token', $token)->with('account.primaryImage')->firstOrFail();

        $qris = PaymentSettings::qris();

        return view('public.booking', compact('booking', 'qris'));
    }

    /** Status ringan untuk pengecekan diam-diam di halaman booking (tidak memuat ulang halaman). */
    public function status(string $token)
    {
        $booking = Booking::where('token', $token)->firstOrFail();

        return response()
            ->json(['status' => $booking->status, 'dp_state' => $booking->dp_state])
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}