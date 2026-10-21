@php
    $protection = old('protection_status', $account->protection_status ?? 'Unprotected');
    $emailStatus = old('email_change_status', $account->email_change_status ?? 'Unchangeable');
@endphp

<div class="grid md:grid-cols-2 gap-4">
    <!-- Status Proteksi: ON / OFF -->
    <div>
        <label class="block text-xs font-semibold text-slate-400 mb-2">Status Proteksi</label>
        <div class="grid grid-cols-2 gap-2">
            <label class="cursor-pointer">
                <input type="radio" name="protection_status" value="Protected" class="peer sr-only" {{ $protection === 'Protected' ? 'checked' : '' }}>
                <div class="text-center py-3 rounded-xl border border-white/10 bg-brand-950/50 text-sm font-bold text-slate-400 transition peer-checked:bg-green-500/15 peer-checked:border-green-500/50 peer-checked:text-green-400">
                    ON
                </div>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="protection_status" value="Unprotected" class="peer sr-only" {{ $protection !== 'Protected' ? 'checked' : '' }}>
                <div class="text-center py-3 rounded-xl border border-white/10 bg-brand-950/50 text-sm font-bold text-slate-400 transition peer-checked:bg-red-500/15 peer-checked:border-red-500/50 peer-checked:text-red-400">
                    OFF
                </div>
            </label>
        </div>
        @error('protection_status') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Status Email/Gmail: Bisa Ganti / Tidak Bisa Ganti -->
    <div>
        <label class="block text-xs font-semibold text-slate-400 mb-2">Status Email/Gmail</label>
        <div class="grid grid-cols-2 gap-2">
            <label class="cursor-pointer">
                <input type="radio" name="email_change_status" value="Changeable" class="peer sr-only" {{ $emailStatus === 'Changeable' ? 'checked' : '' }}>
                <div class="text-center py-3 rounded-xl border border-white/10 bg-brand-950/50 text-sm font-bold text-slate-400 transition peer-checked:bg-green-500/15 peer-checked:border-green-500/50 peer-checked:text-green-400">
                    Bisa Ganti
                </div>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="email_change_status" value="Unchangeable" class="peer sr-only" {{ $emailStatus !== 'Changeable' ? 'checked' : '' }}>
                <div class="text-center py-3 rounded-xl border border-white/10 bg-brand-950/50 text-sm font-bold text-slate-400 transition peer-checked:bg-red-500/15 peer-checked:border-red-500/50 peer-checked:text-red-400">
                    Tidak Bisa Ganti
                </div>
            </label>
        </div>
        @error('email_change_status') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror

        <div class="mt-3">
            <label class="block text-xs font-semibold text-slate-400 mb-2">Email bisa diganti dalam (hari)</label>
            <input type="number" name="email_change_days" min="0" max="365" inputmode="numeric" placeholder="0"
                   value="{{ old('email_change_days', optional($account ?? null)->emailChangeDaysLeft()) }}"
                   class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition @error('email_change_days') border-red-500 @enderror">
            <p class="text-[11px] text-slate-500 mt-1">Untuk stok baru. Isi jumlah hari sampai email bisa diganti; kosong atau 0 = sudah bisa diganti sekarang. Hanya berlaku bila memilih Bisa Ganti.</p>
            @error('email_change_days') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>
</div>