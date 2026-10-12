@extends('layouts.app')

@section('title', $account->name . ' - EHANZSTORE.ID')

@section('content')
<div class="pt-28 pb-20 max-w-7xl mx-auto px-4">
    
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-8">
        <a href="{{ route('home') }}" class="hover:text-electric transition">Beranda</a>
        <span>/</span>
        <a href="{{ route('catalog') }}" class="hover:text-electric transition">Katalog</a>
        <span>/</span>
        <span class="text-white font-medium truncate">{{ $account->name }}</span>
    </nav>

    <div class="grid lg:grid-cols-2 gap-12">
        
        <!-- Galeri Foto -->
        <div class="space-y-4">
            <div class="aspect-[4/3] rounded-3xl overflow-hidden bg-midnight border border-white/10 relative group">
                @php 
                    $primaryImg = $account->images->firstWhere('is_primary', true) ?? $account->images->first(); 
                @endphp
                
                @if($primaryImg)
                    <img id="mainImage" src="{{ Storage::url($primaryImg->image_path) }}" 
                         alt="{{ $account->name }}" 
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center text-slate-600">
                        <svg class="w-16 h-16 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <p class="text-sm">Tidak ada foto tersedia</p>
                    </div>
                @endif
            </div>

            @if($account->images->count() > 1)
            <div class="grid grid-cols-5 gap-3">
                @foreach($account->images as $img)
                <button onclick="changeMainImage('{{ Storage::url($img->image_path) }}')" 
                        class="aspect-square rounded-xl overflow-hidden border-2 {{ $img->id === ($primaryImg->id ?? null) ? 'border-electric' : 'border-transparent hover:border-white/30' }} transition-all thumbnail-btn">
                    <img src="{{ Storage::url($img->image_path) }}" class="w-full h-full object-cover">
                </button>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Informasi Produk -->
        <div class="space-y-8">
            <div>
                <div class="flex items-start justify-between gap-4 mb-4">
                    <h1 class="text-3xl md:text-4xl font-display font-bold text-white leading-tight">{{ $account->name }}</h1>
                    <span class="shrink-0 px-3 py-1.5 bg-electric/20 text-electric text-xs font-bold rounded-lg border border-electric/30">
                        TH {{ $account->town_hall }}
                    </span>
                </div>
                <p class="text-slate-400 text-sm font-mono bg-white/5 inline-block px-3 py-1.5 rounded-lg border border-white/5">
                    KODE: {{ $account->account_code }}
                </p>
            </div>

            <!-- Harga & CTA -->
            <div class="glass rounded-2xl p-6 space-y-4 border-l-4 border-electric">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Harga Jual</p>
                    <p class="text-4xl font-display font-bold text-white">Rp {{ number_format($account->price, 0, ',', '.') }}</p>
                </div>
                
                <a href="https://wa.me/6282130559126?text=Halo%20Admin%20EHANZSTORE%2C%20saya%20tertarik%20dengan%20akun%20*{{ urlencode($account->name) }}*%20(Kode:%20{{ urlencode($account->account_code) }}).%20Apakah%20masih%20tersedia?" 
                   target="_blank"
                   class="flex items-center justify-center gap-3 w-full bg-green-600 hover:bg-green-500 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-green-900/20 hover:shadow-green-900/40 active:scale-[0.98]">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                    Beli via WhatsApp
                </a>
                <p class="text-[10px] text-slate-500 text-center">Anda akan diarahkan ke chat admin dengan pesan otomatis.</p>
            </div>

            <!-- Spesifikasi -->
            <div class="space-y-4">
                <h3 class="text-lg font-display font-bold text-white border-b border-white/10 pb-2">Spesifikasi Akun</h3>
                <div class="grid grid-cols-2 gap-4">
                    @php
                        $specs = [
                            ['label' => 'Barbarian King', 'val' => 'Lv. ' . ($account->barbarian_king_level ?? 0)],
                            ['label' => 'Archer Queen', 'val' => 'Lv. ' . ($account->archer_queen_level ?? 0)],
                            ['label' => 'Grand Warden', 'val' => 'Lv. ' . ($account->grand_warden_level ?? 0)],
                            ['label' => 'Royal Champion', 'val' => 'Lv. ' . ($account->royal_champion_level ?? 0)],
                            ['label' => 'Builder', 'val' => ($account->builder_count ?? 5) . ' Orang'],
                            ['label' => 'Gems', 'val' => number_format($account->gems ?? 0) . ' Gems'],
                        ];
                    @endphp
                    @foreach($specs as $spec)
                    <div class="bg-white/5 rounded-xl p-4 border border-white/5">
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-1">{{ $spec['label'] }}</p>
                        <p class="text-sm font-bold text-white">{{ $spec['val'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Info Keamanan -->
            <div class="space-y-4">
                <h3 class="text-lg font-display font-bold text-white border-b border-white/10 pb-2">Informasi Keamanan</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5">
                        <span class="text-sm text-slate-400">Status Proteksi</span>
                        <span class="text-sm font-bold {{ $account->protection_status === 'Protected' ? 'text-green-400' : 'text-yellow-400' }}">
                            {{ $account->protection_status === 'Protected' ? 'Terproteksi' : 'Tidak Terproteksi' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5">
                        <span class="text-sm text-slate-400">Status Email/Gmail</span>
                        <span class="text-sm font-bold text-white">{{ $account->email_change_status ?? 'Unknown' }}</span>
                    </div>
                </div>
            </div>

            @if($account->description)
            <div class="space-y-2">
                <h3 class="text-lg font-display font-bold text-white border-b border-white/10 pb-2">Deskripsi</h3>
                <p class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">{{ $account->description }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    function changeMainImage(src) {
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.thumbnail-btn').forEach(btn => {
            btn.classList.remove('border-electric');
            btn.classList.add('border-transparent');
        });
        event.currentTarget.classList.remove('border-transparent');
        event.currentTarget.classList.add('border-electric');
    }
</script>
@endpush
@endsection