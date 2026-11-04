@extends('layouts.admin')

@section('title', 'Perangkat Login')

@section('content')
<div class="max-w-3xl mx-auto pb-20">

    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Perangkat Login</h1>
        <p class="text-sm text-slate-400 mt-1">
            Perangkat yang sedang masuk ke akun Anda. Sesi tidak kedaluwarsa sendiri, jadi periksa daftar ini secara berkala.
        </p>
    </div>

    @if(! $supported)
        <div class="glass-panel rounded-2xl p-5 sm:p-6 border border-amber-500/30">
            <p class="text-sm text-amber-300">
                Daftar perangkat belum bisa ditampilkan. Fitur ini membutuhkan <code class="font-mono">SESSION_DRIVER=database</code>
                dan tabel <code class="font-mono">sessions</code> yang sudah dibuat.
            </p>
        </div>
    @else
        {{-- ===== Daftar perangkat ===== --}}
        <div class="glass-panel rounded-2xl divide-y divide-white/5 mb-6">
            @forelse($devices as $d)
                <div class="flex items-start gap-3 p-4 sm:p-5">
                    <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center {{ $d['current'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/5 text-slate-300' }}">
                        @if($d['type'] === 'desktop')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-white">{{ $d['browser'] }} di {{ $d['os'] }}</p>
                            @if($d['current'])
                                <span class="px-2 py-0.5 rounded-full border text-[10px] font-semibold bg-emerald-500/15 text-emerald-300 border-emerald-500/30">Perangkat ini</span>
                            @endif
                        </div>
                        @if($d['model'])
                            <p class="text-xs text-slate-400 mt-0.5">{{ $d['model'] }}</p>
                        @endif
                        <p class="text-xs text-slate-500 mt-1">
                            IP {{ $d['ip'] }} &middot;
                            @if($d['current'])
                                Sedang dipakai sekarang
                            @else
                                Aktif terakhir {{ $d['last_seen'] }} ({{ $d['last_seen_at'] }})
                            @endif
                        </p>
                    </div>
                </div>
            @empty
                <p class="p-6 text-center text-sm text-slate-500">Belum ada sesi tercatat.</p>
            @endforelse
        </div>

        {{-- ===== Keluar dari semua perangkat lain ===== --}}
        <div class="glass-panel rounded-2xl p-5 sm:p-6 border border-red-500/20">
            <div class="flex items-start gap-3">
                <div class="p-2 rounded-xl bg-red-500/15 text-red-300 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-white">Keluar dari semua perangkat lain</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        Pakai ini kalau HP hilang, atau Anda pernah login di perangkat yang bukan milik Anda.
                        Semua perangkat lain langsung keluar dan harus login ulang. Perangkat ini tetap masuk.
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.devices.logout-others') }}" method="POST" class="mt-4 space-y-3"
                  onsubmit="return confirm('Keluarkan semua perangkat lain dari akun ini?')">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Password</label>
                    <input type="password" name="password" required autocomplete="current-password"
                           class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition @error('password') border-red-500 @enderror">
                    @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full sm:w-auto px-5 py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-bold transition">
                    Keluar dari semua perangkat lain
                </button>
            </form>
        </div>
    @endif

</div>
@endsection