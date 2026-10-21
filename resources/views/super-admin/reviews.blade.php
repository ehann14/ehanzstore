@extends('layouts.admin')

@section('title', 'Ulasan Pembeli')

@section('content')
@php
    $tabs = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
    $badge = [
        'pending'  => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        'approved' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
        'rejected' => 'bg-red-500/15 text-red-300 border-red-500/30',
    ];
@endphp

<div class="max-w-5xl mx-auto pb-16">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Ulasan Pembeli</h1>
        <p class="text-sm text-slate-400 mt-1">Ulasan yang disetujui tampil di halaman utama, tepat di bawah akun yang terjual.</p>
    </div>

    <div class="flex gap-2 mb-6 overflow-x-auto no-scrollbar">
        @foreach($tabs as $key => $label)
            <a href="{{ route('super-admin.reviews', ['status' => $key]) }}"
               class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide border transition
                      {{ $status === $key ? 'bg-electric text-white border-electric' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10' }}">
                {{ $label }} ({{ $counts[$key] ?? 0 }})
            </a>
        @endforeach
    </div>

    @if($reviews->isEmpty())
        <div class="glass-panel rounded-3xl p-14 text-center text-slate-400 text-sm">Tidak ada ulasan di tab ini.</div>
    @else
        <div class="space-y-3">
            @foreach($reviews as $review)
                <div class="glass-panel rounded-2xl p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <x-stars :rating="$review->rating" />
                                <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $badge[$review->status] }}">{{ $tabs[$review->status] }}</span>
                            </div>
                            <p class="text-white font-semibold mt-2">{{ $review->reviewer_name }}
                                <span class="text-slate-500 font-normal text-xs">
                                    &middot; pembeli {{ $review->account?->name ?? 'akun dihapus' }}
                                    <span class="font-mono">({{ $review->sale?->account_code_snapshot }})</span>
                                </span>
                            </p>
                        </div>
                        <p class="text-[11px] text-slate-500 whitespace-nowrap">{{ $review->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>
                    </div>

                    <p class="text-sm text-slate-300 mt-3 whitespace-pre-line">{{ $review->comment }}</p>

                    <div class="flex flex-wrap gap-2 mt-4">
                        @if($review->status !== 'approved')
                            <form action="{{ route('super-admin.reviews.approve', $review) }}" method="POST">
                                @csrf
                                <button class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition">Setujui &amp; Tampilkan</button>
                            </form>
                        @endif
                        @if($review->status !== 'rejected')
                            <form action="{{ route('super-admin.reviews.reject', $review) }}" method="POST">
                                @csrf
                                <button class="px-4 py-2 rounded-lg border border-amber-500/30 text-amber-300 hover:bg-amber-500/10 text-xs font-semibold transition">
                                    {{ $review->status === 'approved' ? 'Sembunyikan' : 'Tolak' }}
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('super-admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Hapus ulasan ini permanen?')">
                            @csrf @method('DELETE')
                            <button class="px-4 py-2 rounded-lg border border-red-500/20 text-red-400 hover:bg-red-500/10 text-xs font-semibold transition">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection