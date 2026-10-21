<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewModerationController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true)
            ? $request->query('status')
            : 'pending';

        $reviews = Review::with(['account', 'sale', 'moderator'])
            ->where('status', $status)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $counts = Review::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('super-admin.reviews', compact('reviews', 'status', 'counts'));
    }

    public function approve(Review $review)
    {
        $this->moderate($review, 'approved', 'approve_review');

        return back()->with('success', 'Ulasan disetujui dan sekarang tampil di halaman utama.');
    }

    public function reject(Review $review)
    {
        $this->moderate($review, 'rejected', 'reject_review');

        return back()->with('success', 'Ulasan ditolak dan tidak ditampilkan.');
    }

    public function destroy(Review $review)
    {
        ActivityLog::record('delete_review', $review, 'Ulasan ' . $review->reviewer_name);

        $review->delete();

        return back()->with('success', 'Ulasan dihapus.');
    }

    private function moderate(Review $review, string $status, string $action): void
    {
        $review->update([
            'status'       => $status,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);

        ActivityLog::record($action, $review, 'Ulasan ' . $review->reviewer_name);
    }
}