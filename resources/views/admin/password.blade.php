@extends('layouts.admin')

@section('title', 'Ganti Password')

@section('content')
<div class="max-w-xl mx-auto pb-20">

    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Ganti Password</h1>
        <p class="text-sm text-slate-400 mt-1">
            Gunakan password yang kuat dan jangan dipakai di tempat lain. Setelah diganti, semua perangkat lain akan keluar otomatis.
        </p>
    </div>

    <div class="glass-panel rounded-2xl p-5 sm:p-6">
        <form action="{{ route('admin.password.update') }}" method="POST" class="space-y-4" autocomplete="off">
            @csrf

            <div>
                <label for="current_password" class="block text-xs font-semibold text-slate-400 mb-2">Password lama</label>
                <x-password-input name="current_password" id="current_password" autocomplete="current-password"
                    :input-class="'w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition ' . ($errors->has('current_password') ? 'border-red-500' : '')" />
                @error('current_password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-400 mb-2">Password baru</label>
                <x-password-input name="password" id="password" minlength="8" autocomplete="new-password"
                    :input-class="'w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition ' . ($errors->has('password') ? 'border-red-500' : '')" />
                <p class="text-[11px] text-slate-500 mt-1">Minimal 8 karakter, kombinasi huruf dan angka.</p>
                @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-400 mb-2">Ulangi password baru</label>
                <x-password-input name="password_confirmation" id="password_confirmation" minlength="8" autocomplete="new-password"
                    input-class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition" />
            </div>

            @if($needsCode)
                <div>
                    <label for="code" class="block text-xs font-semibold text-slate-400 mb-2">Kode authenticator</label>
                    <input id="code" type="text" name="code" required inputmode="numeric" maxlength="7" autocomplete="one-time-code" placeholder="123456"
                           class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white font-mono tracking-widest outline-none focus:border-brand-500 transition @error('code') border-red-500 @enderror">
                    @error('code') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            @endif

            <button type="submit" class="w-full sm:w-auto px-5 py-3 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-bold transition">
                Simpan password baru
            </button>
        </form>
    </div>

</div>
@endsection