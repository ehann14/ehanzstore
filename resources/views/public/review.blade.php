@extends('layouts.app')

@section('title', 'Ulasan Pembeli - EHANZSTORE.ID')

@section('content')
@php
    $inputCls = 'w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-electric focus:bg-white/10 outline-none transition';
    $imageUrl = $account?->primaryImage?->image_path ? upload_url($account->primaryImage->image_path) : null;
    $sent     = session('review_sent');
@endphp

<section class="pt-24 sm:pt-32 pb-16 px-4">
    <div class="max-w-xl mx-auto">

        {{-- Akun yang diulas --}}
        <div class="flex items-center gap-4 rounded-2xl bg-slate-900/60 border border-white/[0.07] p-3 mb-6">
            <div class="w-20 h-16 shrink-0 rounded-xl overflow-hidden bg-midnight">
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" alt="" class="w-full h-full object-cover">
                @endif
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-slate-500 uppercase tracking-wider">Ulasan untuk</p>
                <p class="font-display font-bold text-white truncate">{{ $account?->name }}</p>
                <p class="text-[11px] font-mono text-slate-500">{{ $sale->account_code_snapshot }}</p>
            </div>
        </div>

        @if($review)
            {{-- Sudah mengisi ulasan --}}
            <div class="rounded-2xl bg-slate-900/60 border border-white/[0.07] p-6 sm:p-8 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-emerald-500/15 text-emerald-400 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h1 class="font-display text-xl font-bold text-white">
                    {{ $sent ? 'Terima kasih atas ulasanmu!' : 'Ulasan sudah dikirim' }}
                </h1>
                <p class="text-sm text-slate-400 mt-2">
                    @if($review->status === 'approved')
                        Ulasanmu sudah tampil di halaman utama EHANZSTORE.ID.
                    @elseif($review->status === 'rejected')
                        Ulasan ini tidak ditampilkan di website.
                    @else
                        Ulasanmu akan tampil di halaman utama setelah dicek oleh admin.
                    @endif
                </p>
                <div class="mt-5 rounded-xl bg-white/[0.03] border border-white/5 p-4 text-left">
                    <x-stars :rating="$review->rating" />
                    <p class="text-sm text-slate-300 mt-2 whitespace-pre-line">{{ $review->comment }}</p>
                    <p class="text-xs text-slate-500 mt-2">&mdash; {{ $review->reviewer_name }}</p>
                </div>
                <a href="{{ route('home') }}" class="inline-block mt-6 text-sm text-electric hover:text-white transition">Kembali ke beranda</a>
            </div>
        @else
            <div class="rounded-2xl bg-slate-900/60 border border-white/[0.07] p-6 sm:p-8">
                <h1 class="font-display text-xl sm:text-2xl font-bold text-white">Bagaimana pengalaman belanjamu?</h1>
                <p class="text-sm text-slate-400 mt-1">Ceritakan pengalamanmu membeli di EHANZSTORE.ID. Ulasanmu membantu pembeli lain.</p>

                <form action="{{ route('review.store', $sale->review_token) }}" method="POST" class="mt-6 space-y-5"
                      x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }">
                    @csrf

                    {{-- Jebakan bot, jangan diisi --}}
                    <div class="hidden" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Penilaian</label>
                        <div class="flex items-center gap-1" @mouseleave="hover = 0">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}"
                                        class="p-0.5 transition-transform hover:scale-110" aria-label="{{ $i }} bintang">
                                    <svg class="w-9 h-9 transition-colors" :class="(hover || rating) >= {{ $i }} ? 'text-amber-400' : 'text-slate-700'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" :value="rating">
                        @error('rating') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="reviewer_name" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama yang ditampilkan</label>
                        <input id="reviewer_name" type="text" name="reviewer_name" maxlength="60" required
                               value="{{ old('reviewer_name', \Illuminate\Support\Str::of($sale->buyer_name)->before(' ')) }}"
                               placeholder="Contoh: Budi" class="{{ $inputCls }}">
                        <p class="text-[11px] text-slate-500 mt-1">Cukup nama panggilan. Nomor WhatsApp tidak akan ditampilkan.</p>
                        @error('reviewer_name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="comment" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Ulasan</label>
                        <textarea id="comment" name="comment" rows="5" maxlength="500" required
                                  placeholder="Akunnya sesuai deskripsi, admin ramah, serah terima cepat..."
                                  class="{{ $inputCls }} resize-none">{{ old('comment') }}</textarea>
                        <p class="text-[11px] text-slate-500 mt-1">Minimal 10 karakter, maksimal 500.</p>
                        @error('comment') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="w-full bg-electric hover:bg-blue-500 text-white font-bold py-3.5 rounded-xl transition active:scale-[0.98]">
                        Kirim Ulasan
                    </button>
                    <p class="text-[11px] text-slate-500 text-center">Ulasan dicek admin dulu sebelum tampil di website.</p>
                </form>
            </div>
        @endif
    </div>
</section>
@endsection