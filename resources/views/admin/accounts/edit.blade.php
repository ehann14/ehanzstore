@extends('layouts.admin')

@section('title', 'Edit Akun')

@section('content')
<div class="max-w-3xl mx-auto pb-20">
    <!-- Header Form -->
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.accounts.index') }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 transition text-slate-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">Edit Akun {{ $account->account_code }}</h1>
            <p class="text-sm text-slate-400">Perbarui data, foto, dan status akun.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            <p class="font-semibold mb-1">Data belum bisa disimpan:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Bagikan akun (link + teks promosi) --}}
    @include('admin.accounts._share', ['account' => $account])

    <!-- Form Card -->
    <form id="accountForm" action="{{ route('admin.accounts.update', $account) }}" method="POST" enctype="multipart/form-data" class="glass-panel rounded-3xl p-6 md:p-8 space-y-8">
        @csrf
        @method('PUT')

        <!-- Informasi Dasar -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider border-b border-white/5 pb-2">Informasi Dasar</h3>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Nama Akun <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $account->name) }}" required placeholder="Contoh: TH16 Max Hero Full"
                           class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Town Hall <span class="text-red-400">*</span></label>
                    <select name="town_hall" required class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition @error('town_hall') border-red-500 @enderror">
                        <option value="">Pilih Level TH</option>
                        @for($i = 1; $i <= 16; $i++)
                            <option value="{{ $i }}" {{ old('town_hall', $account->town_hall) == $i ? 'selected' : '' }}>TH {{ $i }}</option>
                        @endfor
                    </select>
                    @error('town_hall') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-2">Kode Akun <span class="text-red-400">*</span></label>
                <input type="text" name="account_code" value="{{ old('account_code', $account->account_code) }}" required maxlength="30" placeholder="Contoh: EHZ-001"
                       class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white font-mono uppercase focus:border-brand-500 outline-none transition @error('account_code') border-red-500 @enderror">
                <p class="text-[11px] text-slate-500 mt-1">Isi manual. Hanya huruf, angka, tanda minus (-) dan underscore (_). Kode harus unik karena dipakai di link akun.</p>
                @error('account_code') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-2">Harga (Rp) <span class="text-red-400">*</span></label>
                <input type="number" name="price" value="{{ old('price', (int) $account->price) }}" required min="0" max="999999999" placeholder="500000"
                       class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition @error('price') border-red-500 @enderror">
                @error('price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Spesifikasi Akun -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider border-b border-white/5 pb-2">Spesifikasi & Keamanan</h3>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @php
                    $heroes = [
                        ['name' => 'barbarian_king_level', 'label' => 'Barbarian King'],
                        ['name' => 'archer_queen_level', 'label' => 'Archer Queen'],
                        ['name' => 'minion_prince_level', 'label' => 'Minion Prince'],
                        ['name' => 'grand_warden_level', 'label' => 'Grand Warden'],
                        ['name' => 'royal_champion_level', 'label' => 'Royal Champion (Jawara Royal)'],
                        ['name' => 'dragon_duke_level', 'label' => 'Dragon Duke (Adipati Naga)']
                    ];
                @endphp

                @foreach($heroes as $hero)
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 mb-1.5 uppercase">{{ $hero['label'] }}</label>
                    <input type="number" name="{{ $hero['name'] }}" value="{{ old($hero['name'], $account->{$hero['name']} ?? 0) }}" min="0" max="90"
                           class="w-full bg-brand-950/50 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:border-brand-500 outline-none transition">
                </div>
                @endforeach
            </div>

            @include('admin.accounts._status', ['account' => $account])
        </div>

        @include('admin.accounts._photos', ['account' => $account])

        <!-- Status -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider border-b border-white/5 pb-2">Status</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Status Terbit</label>
                    <select name="publication_status" class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition">
                        <option value="draft" {{ old('publication_status', $account->publication_status) == 'draft' ? 'selected' : '' }}>Draft (disembunyikan)</option>
                        <option value="published" {{ old('publication_status', $account->publication_status) == 'published' ? 'selected' : '' }}>Terbit (tampil di katalog)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Status Penjualan</label>
                    <select name="sale_status" data-current="{{ $account->sale_status }}"
                            onchange="document.getElementById('soldHint').classList.toggle('hidden', this.value !== 'sold' || this.dataset.current === 'sold')"
                            class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition">
                        <option value="available" {{ old('sale_status', $account->sale_status) == 'available' ? 'selected' : '' }}>Tersedia</option>
                        <option value="sold" {{ old('sale_status', $account->sale_status) == 'sold' ? 'selected' : '' }}>Terjual</option>
                        <option value="archived" {{ old('sale_status', $account->sale_status) == 'archived' ? 'selected' : '' }}>Diarsipkan</option>
                    </select>
                    <p id="soldHint" class="hidden text-[11px] text-amber-300 mt-1.5">
                        Setelah disimpan, kamu akan diminta mengisi data pembeli untuk garansi {{ \App\Models\Sale::WARRANTY_DAYS }} hari.
                    </p>
                </div>
            </div>
        </div>

        <!-- Deskripsi & Aksi -->
        <div class="space-y-6 pt-4">
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-2">Deskripsi Tambahan</label>
                <textarea name="description" rows="3" placeholder="Tulis detail tambahan seperti CN, builder, gems, dll..."
                          class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition resize-none">{{ old('description', $account->description) }}</textarea>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-white/5">
                <button type="submit" id="submitBtn" class="bg-brand-600 hover:bg-brand-500 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg shadow-brand-600/20 active:scale-95">
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.accounts.index') }}" class="text-slate-400 hover:text-white text-sm font-medium transition">
                    Batal
                </a>
            </div>
        </div>
    </form>
</div>