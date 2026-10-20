@extends('layouts.admin')

@section('title', 'Tambah Akun Baru')

@section('content')
<div class="max-w-3xl mx-auto pb-20">
    <!-- Header Form -->
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.accounts.index') }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 transition text-slate-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">Tambah Akun COC</h1>
            <p class="text-sm text-slate-400">Isi data akun dengan lengkap dan jujur.</p>
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

    <!-- Form Card -->
    <form id="accountForm" action="{{ route('admin.accounts.store') }}" method="POST" enctype="multipart/form-data" class="glass-panel rounded-3xl p-6 md:p-8 space-y-8">
        @csrf

        <!-- Informasi Dasar -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider border-b border-white/5 pb-2">Informasi Dasar</h3>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Nama Akun <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: TH16 Max Hero Full"
                           class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Town Hall <span class="text-red-400">*</span></label>
                    <select name="town_hall" required class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition @error('town_hall') border-red-500 @enderror">
                        <option value="">Pilih Level TH</option>
                        @for($i = 1; $i <= 16; $i++)
                            <option value="{{ $i }}" {{ old('town_hall') == $i ? 'selected' : '' }}>TH {{ $i }}</option>
                        @endfor
                    </select>
                    @error('town_hall') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-2">Kode Akun <span class="text-red-400">*</span></label>
                <input type="text" name="account_code" value="{{ old('account_code') }}" required maxlength="30" placeholder="Contoh: EHZ-001"
                       class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white font-mono uppercase focus:border-brand-500 outline-none transition @error('account_code') border-red-500 @enderror">
                <p class="text-[11px] text-slate-500 mt-1">Isi manual. Hanya huruf, angka, tanda minus (-) dan underscore (_). Kode harus unik karena dipakai di link akun.</p>
                @error('account_code') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-2">Harga (Rp) <span class="text-red-400">*</span></label>
                <input type="number" name="price" value="{{ old('price') }}" required min="0" max="999999999" placeholder="500000"
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
                    <input type="number" name="{{ $hero['name'] }}" value="{{ old($hero['name'], 0) }}" min="0" max="90"
                           class="w-full bg-brand-950/50 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:border-brand-500 outline-none transition">
                </div>
                @endforeach
            </div>

            @include('admin.accounts._status', ['account' => null])
        </div>

        @include('admin.accounts._photos', ['account' => null])

        <!-- Deskripsi & Aksi -->
        <div class="space-y-6 pt-4">
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-2">Deskripsi Tambahan</label>
                <textarea name="description" rows="3" placeholder="Tulis detail tambahan seperti CN, builder, gems, dll..."
                          class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition resize-none">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-white/5">
                <button type="submit" id="submitBtn" class="bg-brand-600 hover:bg-brand-500 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg shadow-brand-600/20 active:scale-95">
                    Simpan Akun
                </button>
                <a href="{{ route('admin.accounts.index') }}" class="text-slate-400 hover:text-white text-sm font-medium transition">
                    Batal
                </a>
            </div>
        </div>
    </form>
</div>

@endsection