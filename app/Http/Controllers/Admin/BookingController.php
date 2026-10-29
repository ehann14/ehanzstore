<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\CocAccount;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /** Daftar booking. Admin biasa hanya melihat booking akun miliknya; Super Admin melihat semua. */
    public function index(Request $request)
    {
        $filter = $request->query('status', 'open');
        $search = trim((string) $request->query('q', ''));

        $query = $this->scopedQuery()->with('account.primaryImage')->latest('id');

        $query->when($filter === 'open', fn ($q) => $q->whereIn('status', [Booking::PENDING, Booking::ACTIVE]))
              ->when($filter === 'decide', fn ($q) => $q->where('status', Booking::EXPIRED)->where('dp_state', Booking::DP_HELD))
              ->when($filter === 'forfeited', fn ($q) => $q->where('dp_state', Booking::DP_FORFEITED))
              ->when(in_array($filter, [Booking::PENDING, Booking::ACTIVE, Booking::COMPLETED, Booking::CANCELLED, Booking::EXPIRED], true),
                  fn ($q) => $q->where('status', $filter));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('buyer_phone', 'like', '%' . preg_replace('/\D+/', '', $search) . '%')
                  ->orWhere('account_code_snapshot', 'like', "%{$search}%");
            });
        }

        $bookings = $query->paginate(15)->withQueryString();

        $counts = [
            'pending'   => $this->scopedQuery()->where('status', Booking::PENDING)->count(),
            'active'    => $this->scopedQuery()->where('status', Booking::ACTIVE)->count(),
            'decide'    => $this->scopedQuery()->where('status', Booking::EXPIRED)->where('dp_state', Booking::DP_HELD)->count(),
            'forfeited' => $this->scopedQuery()->where('dp_state', Booking::DP_FORFEITED)->count(),
        ];

        // Total DP yang hangus (uang DP yang tidak dikembalikan karena pembeli tidak melunasi / membatalkan)
        $forfeitedTotal = (float) $this->scopedQuery()->where('dp_state', Booking::DP_FORFEITED)->sum('dp_amount');

        return view('admin.bookings.index', compact('bookings', 'filter', 'search', 'counts', 'forfeitedTotal'));
    }

    /** Form catat booking DP dari Kelola Akun (pembeli bayar DP dulu, lunas dalam 7 / 14 hari). */
    public function createForAccount(CocAccount $account)
    {
        $this->authorizeAccount($account);

        if ($account->sale_status !== 'available') {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'Hanya akun yang masih tersedia yang bisa dibooking.');
        }

        if ($held = Booking::holdingFor($account->id)) {
            return redirect()
                ->route('admin.bookings.index', ['status' => 'open', 'q' => $held->booking_code])
                ->with('error', "Akun ini sudah dibooking ({$held->booking_code}).");
        }

        $account->load('primaryImage');

        return view('admin.accounts.booking', compact('account'));
    }

    /** Simpan booking DP: akun langsung ditahan sampai batas pelunasan. */
    public function storeForAccount(Request $request, CocAccount $account)
    {
        $this->authorizeAccount($account);

        $data = $request->validate([
            'buyer_name'  => ['required', 'string', 'min:2', 'max:100'],
            'buyer_phone' => [
                'required', 'string', 'max:25',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $phone = Sale::normalizePhone($value);

                    if (! $phone || ! preg_match('/^628[0-9]{8,12}$/', $phone)) {
                        $fail('Nomor WhatsApp pembeli tidak valid.');
                    }
                },
            ],
            'dp_amount'   => ['required', 'numeric', 'min:1000', 'max:999999999'],
            'hold_days'   => ['required', 'integer', Rule::in(Booking::holdOptions())],
            'notes'       => ['nullable', 'string', 'max:500'],
        ], [
            'buyer_name.required'  => 'Nama pembeli wajib diisi.',
            'buyer_name.min'       => 'Nama pembeli minimal 2 karakter.',
            'buyer_phone.required' => 'Nomor WhatsApp pembeli wajib diisi.',
            'dp_amount.required'   => 'Nominal DP wajib diisi.',
            'dp_amount.numeric'    => 'Nominal DP harus berupa angka.',
            'dp_amount.min'        => 'Nominal DP minimal Rp 1.000.',
            'hold_days.required'   => 'Pilih batas pelunasan.',
            'hold_days.in'         => 'Batas pelunasan harus ' . implode(' atau ', Booking::holdOptions()) . ' hari.',
        ]);

        $data['buyer_phone'] = Sale::normalizePhone($data['buyer_phone']);

        [$booking, $error] = Booking::placeByAdmin($account, $data, Auth::id());

        if (! $booking) {
            return back()->withInput()->with('error', $error);
        }

        ActivityLog::record('create_booking', $account, 'Akun ' . $account->account_code, [
            'booking' => $booking->booking_code,
            'buyer'   => $booking->buyer_name,
            'dp'      => $booking->dp_amount,
            'days'    => (int) $data['hold_days'],
        ]);

        return redirect()->route('admin.accounts.index')->with(
            'success',
            "Booking {$booking->booking_code} dicatat. Akun {$account->account_code} ditahan untuk {$booking->buyer_name}, "
            . "harus lunas (sisa Rp " . number_format($booking->remaining(), 0, ',', '.') . ") sebelum {$booking->deadlineLabel()}. "
            . 'Lewat batas itu akun otomatis ditarik kembali dan DP hangus.'
        );
    }

    /** DP sudah diterima -> akun ditahan untuk pembeli (7 atau 14 hari, dipilih admin). */
    public function confirm(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $days = Booking::normalizeHoldDays($request->filled('days') ? (int) $request->input('days') : null);

        if ($error = $booking->confirmDp(Auth::id(), $days)) {
            return back()->with('error', $error);
        }

        ActivityLog::record('confirm_booking_dp', $booking->account, 'Booking ' . $booking->booking_code, [
            'buyer' => $booking->buyer_name,
            'dp'    => $booking->dp_amount,
            'days'  => $days,
        ]);

        return back()->with('success', "DP booking {$booking->booking_code} dikonfirmasi. Akun ditahan sampai {$booking->deadlineLabel()}.");
    }

    public function extend(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $days = (int) $request->input('days', 1);
        $days = max(1, min(14, $days));

        $booking->extend($days);

        ActivityLog::record('extend_booking', $booking->account, 'Booking ' . $booking->booking_code, ['days' => $days]);

        return back()->with('success', "Batas pelunasan {$booking->booking_code} diperpanjang {$days} hari.");
    }

    /** Batalkan booking (oleh pembeli: DP hangus, atau oleh toko: DP dikembalikan). */
    public function cancel(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $data = $request->validate([
            'by'     => ['required', 'in:buyer,store'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        $byStore = $data['by'] === 'store';

        $booking->cancel($byStore, $data['reason'] ?? null);

        ActivityLog::record('cancel_booking', $booking->account, 'Booking ' . $booking->booking_code, [
            'by'       => $byStore ? 'toko' : 'pembeli',
            'dp_state' => $booking->dp_state,
        ]);

        $note = $booking->dp_state === Booking::DP_REFUNDED
            ? ' Kembalikan DP Rp ' . number_format((float) $booking->dp_amount, 0, ',', '.') . ' ke pembeli.'
            : ($booking->dp_state === Booking::DP_FORFEITED ? ' DP dinyatakan hangus.' : '');

        return back()->with('success', "Booking {$booking->booking_code} dibatalkan.{$note}");
    }

    /** Booking kedaluwarsa (data lama) yang DP-nya masih ditahan: putuskan hangus atau kembalikan. */
    public function resolve(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $data = $request->validate(['outcome' => ['required', 'in:forfeit,refund']]);

        $booking->resolveHeldDp($data['outcome'] === 'forfeit');

        ActivityLog::record('resolve_booking_dp', $booking->account, 'Booking ' . $booking->booking_code, [
            'dp_state' => $booking->dp_state,
        ]);

        return back()->with('success', "DP booking {$booking->booking_code}: " . strtolower($booking->dpLabel()) . '.');
    }

    private function scopedQuery()
    {
        $query = Booking::query();

        if (! Auth::user()->isSuperAdmin()) {
            $query->whereHas('account', fn ($q) => $q->where('created_by', Auth::id()));
        }

        return $query;
    }

    private function authorizeBooking(Booking $booking): void
    {
        $user = Auth::user();

        if (! $user->isSuperAdmin() && $booking->account?->created_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function authorizeAccount(CocAccount $account): void
    {
        $user = Auth::user();

        if ($account->created_by !== $user->id && ! $user->isSuperAdmin()) {
            abort(403);
        }
    }
}