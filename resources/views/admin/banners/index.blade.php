@extends('layouts.admin')

@section('title', 'Banner Beranda')

@section('content')
<div class="max-w-6xl mx-auto pb-20">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">Banner Beranda</h1>
            <p class="text-sm text-slate-400 mt-1">Banner tampil sebagai slider otomatis di bagian atas beranda. Atur jadwal tayang agar banner promo muncul dan hilang sendiri sesuai tanggal.</p>
        </div>
        <a href="{{ route('admin.banners.create') }}"
           class="inline-flex items-center justify-center gap-2 bg-electric hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Posting Banner
        </a>
    </div>

    @if($banners->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center">
            <p class="text-slate-400 mb-2">Belum ada banner.</p>
            <p class="text-sm text-slate-500">Selama kosong, beranda menampilkan banner bawaan toko.</p>
        </div>
    @else
        <div class="grid md:grid-cols-2 gap-6">
            @foreach($banners as $banner)
                <div class="glass-panel rounded-2xl overflow-hidden {{ in_array($banner->scheduleState(), ['live', 'scheduled'], true) ? '' : 'opacity-60' }}">
                    <div class="relative aspect-[2/1] bg-midnight">
                        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">

                        <!-- Tombol X di pojok kanan: hapus banner -->
                        <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST"
                              onsubmit="return confirm('Hapus banner ini? Tindakan ini tidak bisa dibatalkan.')"
                              class="absolute top-3 right-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Hapus banner" aria-label="Hapus banner"
                                    class="w-9 h-9 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center backdrop-blur transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>

                        @php
                            $state = $banner->scheduleState();
                            $stateBadge = [
                                'live'      => ['Tampil', 'bg-emerald-500/90 text-white'],
                                'scheduled' => ['Terjadwal', 'bg-sky-500/90 text-white'],
                                'ended'     => ['Sudah berakhir', 'bg-red-600/90 text-white'],
                                'hidden'    => ['Disembunyikan', 'bg-slate-700 text-slate-300'],
                            ][$state];
                        @endphp
                        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-md text-[11px] font-semibold {{ $stateBadge[1] }}">
                            {{ $stateBadge[0] }}
                        </span>
                    </div>

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-white truncate">{{ $banner->title }}</h3>
                                <p class="text-xs text-slate-500 mt-1 truncate">
                                    @if($banner->link_url)
                                        {{ ucfirst($banner->link_type) }} &middot; {{ $banner->link_url }}
                                    @else
                                        Tanpa link
                                    @endif
                                </p>
                            </div>
                            <span class="shrink-0 text-xs text-slate-500">Urutan {{ $banner->sort_order }}</span>
                        </div>

                        @if($banner->scheduleLabel())
                            <p class="text-xs mt-2 flex items-start gap-1.5 {{ $state === 'scheduled' ? 'text-sky-300' : ($state === 'ended' ? 'text-red-300' : 'text-slate-400') }}">
                                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>{{ $banner->scheduleLabel() }}</span>
                            </p>
                        @endif

                        <div class="flex gap-2 mt-4">
                            <a href="{{ route('admin.banners.edit', $banner) }}"
                               class="flex-1 text-center py-2 rounded-lg border border-white/10 text-sm text-slate-300 hover:bg-white/5 transition">Edit</a>
                            <form action="{{ route('admin.banners.toggle', $banner) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full py-2 rounded-lg border border-white/10 text-sm text-slate-300 hover:bg-white/5 transition">
                                    {{ $banner->is_active ? 'Sembunyikan' : 'Tampilkan' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection