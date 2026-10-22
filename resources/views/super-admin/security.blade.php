@extends('layouts.admin')

@section('title', 'Keamanan Akun')

@section('content')
<div class="max-w-3xl mx-auto pb-20">

    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Keamanan Akun</h1>
        <p class="text-sm text-slate-400 mt-1">Lindungi akun Super Admin yang menyimpan data pembeli dan laba toko.</p>
    </div>

    @if(session('recovery_codes'))
        <div class="glass-panel rounded-2xl p-5 sm:p-6 mb-6 border border-amber-500/30">
            <div class="flex items-start gap-3 mb-4">
                <div class="p-2 rounded-xl bg-amber-500/15 text-amber-300 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-white">Simpan kode pemulihan ini sekarang</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        Kode ini satu-satunya jalan masuk bila HP Anda hilang. Tiap kode hanya berlaku sekali dan
                        <strong class="text-amber-300">tidak akan ditampilkan lagi</strong> setelah halaman ini ditutup.
                        Simpan di pengelola password atau cetak, jangan di HP yang sama.
                    </p>
                </div>
            </div>

            <div id="recoveryBox" class="grid grid-cols-2 gap-2 font-mono text-sm">
                @foreach(session('recovery_codes') as $code)
                    <div class="rounded-lg bg-brand-950/60 border border-white/10 px-3 py-2 text-center text-white tracking-wider select-all">{{ $code }}</div>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
                <button type="button" id="copyCodes"
                        class="px-4 py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-xs font-semibold text-slate-200 transition">Salin semua</button>
                <button type="button" onclick="window.print()"
                        class="px-4 py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-xs font-semibold text-slate-200 transition">Cetak</button>
            </div>
        </div>
    @endif

    @if($enabled)
        {{-- ===== 2FA AKTIF ===== --}}
        <div class="glass-panel rounded-2xl p-5 sm:p-6 mb-6">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-xl bg-emerald-500/15 text-emerald-300 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-white">Verifikasi dua langkah aktif</h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Aktif sejak {{ $user->two_factor_confirmed_at->timezone(\App\Models\Sale::DISPLAY_TZ)->format('d M Y, H:i') }} WIB.
                            Login membutuhkan password dan kode dari aplikasi authenticator.
                        </p>
                    </div>
                </div>
                <span class="shrink-0 px-2.5 py-1 rounded-full border text-[11px] font-semibold bg-emerald-500/15 text-emerald-300 border-emerald-500/30">Aktif</span>
            </div>
        </div>

        <div class="glass-panel rounded-2xl p-5 sm:p-6 mb-6">
            <h3 class="font-bold text-white">Kode pemulihan</h3>
            <p class="text-sm mt-1 {{ $user->recoveryCodesLeft() <= 2 ? 'text-amber-300' : 'text-slate-400' }}">
                Tersisa {{ $user->recoveryCodesLeft() }} dari 8 kode.
                @if($user->recoveryCodesLeft() <= 2) Segera buat kode baru. @endif
            </p>

            <form action="{{ route('super-admin.security.recovery') }}" method="POST" class="mt-4 space-y-3"
                  onsubmit="return confirm('Kode pemulihan lama akan langsung tidak berlaku. Lanjutkan?')">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2">Password</label>
                        <input type="password" name="password" required autocomplete="current-password"
                               class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition @error('password') border-red-500 @enderror">
                        @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2">Kode authenticator</label>
                        <input type="text" name="code" required inputmode="numeric" maxlength="7" autocomplete="one-time-code" placeholder="123456"
                               class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white font-mono tracking-widest outline-none focus:border-brand-500 transition @error('code') border-red-500 @enderror">
                        @error('code') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-semibold transition">Buat kode pemulihan baru</button>
            </form>
        </div>

        @unless($required)
            <div class="glass-panel rounded-2xl p-5 sm:p-6 border border-red-500/20">
                <h3 class="font-bold text-white">Nonaktifkan verifikasi dua langkah</h3>
                <p class="text-xs text-slate-400 mt-1">Tidak disarankan. Akun akan kembali hanya dilindungi password.</p>

                <form action="{{ route('super-admin.security.disable') }}" method="POST" class="mt-4 space-y-3"
                      onsubmit="return confirm('Yakin menonaktifkan verifikasi dua langkah?')">
                    @csrf
                    <div class="grid sm:grid-cols-2 gap-3">
                        <input type="password" name="password" required placeholder="Password" autocomplete="current-password"
                               class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-red-500 transition">
                        <input type="text" name="code" required inputmode="numeric" maxlength="7" placeholder="Kode authenticator" autocomplete="one-time-code"
                               class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white font-mono tracking-widest outline-none focus:border-red-500 transition">
                    </div>
                    <button type="submit" class="px-5 py-2.5 rounded-xl border border-red-500/40 text-red-300 hover:bg-red-500/10 text-sm font-semibold transition">Nonaktifkan</button>
                </form>
            </div>
        @else
            <p class="text-xs text-slate-500">Verifikasi dua langkah diwajibkan untuk Super Admin, jadi tidak bisa dinonaktifkan dari sini.</p>
        @endunless

    @else
        {{-- ===== SETUP ===== --}}
        <div class="glass-panel rounded-2xl p-5 sm:p-6 mb-6 {{ $required ? 'border border-amber-500/30' : '' }}">
            <div class="flex items-start gap-3">
                <div class="p-2 rounded-xl bg-amber-500/15 text-amber-300 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-white">Aktifkan verifikasi dua langkah</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        @if($required) Wajib untuk Super Admin. @endif
                        Walau password bocor, orang lain tetap tidak bisa masuk tanpa kode dari HP Anda.
                    </p>
                </div>
            </div>
        </div>

        <div class="glass-panel rounded-2xl p-5 sm:p-6 space-y-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Langkah 1 &middot; Scan kode QR</p>
                <p class="text-sm text-slate-300 mb-4">
                    Install Google Authenticator, Microsoft Authenticator, atau Authy di HP, pilih tambah akun, lalu scan:
                </p>

                <div class="flex flex-col sm:flex-row gap-5 items-start">
                    <div class="bg-white p-3 rounded-2xl shrink-0">
                        <div id="qrcode" class="w-[180px] h-[180px] flex items-center justify-center text-[11px] text-slate-500 text-center">Memuat kode QR...</div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-400 mb-2">Tidak bisa scan? Masukkan kunci ini secara manual (jenis: berbasis waktu):</p>
                        <div class="rounded-xl bg-brand-950/60 border border-white/10 px-4 py-3 font-mono text-sm sm:text-base text-white tracking-wider break-all select-all">{{ $prettySecret }}</div>
                        <p class="text-[11px] text-slate-500 mt-2">Akun: {{ $user->email }}</p>

                        <form action="{{ route('super-admin.security.refresh') }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="text-xs text-slate-500 hover:text-white underline underline-offset-2 transition">Buat kunci baru</button>
                        </form>
                    </div>
                </div>
            </div>

            <form action="{{ route('super-admin.security.enable') }}" method="POST" class="border-t border-white/5 pt-6">
                @csrf
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Langkah 2 &middot; Konfirmasi kode</p>
                <label class="block text-sm text-slate-300 mb-2">Masukkan kode 6 digit yang tampil di aplikasi:</label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" name="code" required inputmode="numeric" maxlength="7" autocomplete="one-time-code" placeholder="123456"
                           class="w-full sm:w-56 bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white text-lg font-mono tracking-[0.3em] text-center outline-none focus:border-brand-500 transition @error('code') border-red-500 @enderror">
                    <button type="submit" class="px-6 py-3 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-semibold transition">Aktifkan</button>
                </div>
                @error('code') <p class="text-red-400 text-xs mt-2">{{ $message }}</p> @enderror
            </form>
        </div>
    @endif
</div>
@endsection

@push('scripts')
@if(! $enabled)
    {{-- Kode QR dibuat di browser Anda; kunci rahasia tidak dikirim ke pihak ketiga --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        (function () {
            var box = document.getElementById('qrcode');
            if (!box) return;

            if (typeof QRCode === 'undefined') {
                box.textContent = 'QR gagal dimuat. Pakai kunci manual di samping.';
                return;
            }

            box.textContent = '';
            new QRCode(box, {
                text: @json($otpauthUri),
                width: 180,
                height: 180,
                correctLevel: QRCode.CorrectLevel.M
            });
        })();
    </script>
@endif

@if(session('recovery_codes'))
    <script>
        (function () {
            var btn = document.getElementById('copyCodes');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var text = Array.from(document.querySelectorAll('#recoveryBox > div')).map(function (d) { return d.textContent.trim(); }).join('\n');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function () { btn.textContent = 'Tersalin'; });
                }
            });
        })();
    </script>
@endif
@endpush