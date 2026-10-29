<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'pending' => $this->scopedQuery()->where('status', Booking::PENDING)->count(),
            'active'  => $this->scopedQuery()->where('status', Booking::ACTIVE)->count(),
            'decide'  => $this->scopedQuery()->where('status', Booking::EXPIRED)->where('dp_state', Booking::DP_HELD)->count(),
        ];

        return view('admin.bookings.index', compact('bookings', 'filter', 'search', 'counts'));
    }

    /** DP sudah diterima -> akun ditahan untuk pembeli. */
    public function confirm(Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($error = $booking->confirmDp(Auth::id())) {
            return back()->with('error', $error);
        }

        ActivityLog::record('confirm_booking_dp', $booking->account, 'Booking ' . $booking->booking_code, [
            'buyer' => $booking->buyer_name,
            'dp'    => $booking->dp_amount,
        ]);

        return back()->with('success', "DP booking {$booking->booking_code} dikonfirmasi. Akun ditahan sampai {$booking->deadlineLabel()}.");
    }

    public function extend(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $days = (int) $request->input('days', 1);
        $days = max(1, min(7, $days));

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

    /** Booking kedaluwarsa yang DP-nya masih ditahan: putuskan hangus atau kembalikan. */
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
}