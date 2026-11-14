@extends('layouts.admin')

@section('title', 'Hemat Bandwidth')

@section('content')
@php
    $fmt = fn ($b) => \App\Support\Housekeeping::bytes($b);
    $dirLabels = ['accounts' => 'Foto akun', 'banners' => 'Banner beranda', 'maintenance' => 'Gambar maintenance', 'payment' => 'QRIS pembayaran'];
    $btn = 'inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold transition';
@endphp

<div class="max-w-3xl mx-auto pb-16 space-y-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Hemat Bandwidth &amp; Cache</h1>
        <p class="text-sm text-slate-400 mt-1">Kuota hosting habis terutama karena gambar berukuran besar yang diunduh berulang kali dan halaman admin yang auto-update terus. Semua alat di bawah aman: tidak menyentuh data penjualan, booking, atau sesi login.</p>
    </div>

    {{-- 1. Mode hemat auto-update --}}
    <div class="glass-panel rounded-3xl p-5 sm:p-6 {{ $saverOn ? 'border-emerald-500/30' : '' }}">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[220px]">
                <p class="font-display font-bold text-white text-lg">
                    Mode Hemat Auto-Update:
                    <span class="{{ $saverOn ? 'text-emerald-300' : 'text-amber-300' }}">{{ $saverOn ? 'AKTIF' : 'Mati' }}</span>
                </p>
                <p class="text-xs text-slate-400 mt-1">
                    Dashboard Super Admin mengunduh ulang seluruh halaman tiap 20 detik, dan lonceng notifikasi memanggil server tiap 20 detik selama tab terbuka.
                    Saat mode ini aktif, keduanya jadi {{ $saverX }}x lebih jarang (sekitar tiap 2 menit). Notifikasi push ke HP tetap berjalan normal.
                </p>
            </div>
            <form method="POST" action="{{ route('super-admin.bandwidth.saver') }}">
                @csrf
                <input type="hidden" name="on" value="{{ $saverOn ? 0 : 1 }}">
                <button type="submit" class="{{ $btn }} {{ $saverOn ? 'border border-white/10 bg-white/5 hover:bg-white/10 text-white' : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950' }}">
                    {{ $saverOn ? 'Matikan' : 'Aktifkan' }}
                </button>
            </form>
        </div>
    </div>

    {{-- 2. Gambar --}}
    <div class="glass-panel rounded-3xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start gap-4 mb-4">
            <div class="flex-1 min-w-[220px]">
                <p class="font-display font-bold text-white text-lg">Gambar (penyebab bandwidth terbesar)</p>
                <p class="text-xs text-slate-400 mt-1">
                    Total folder uploads: <span class="text-white font-semibold">{{ $fmt($uploads['total']) }}</span>.
                    Tiap pengunjung beranda mengunduh banner, dan tiap foto akun diunduh berulang oleh banyak orang.
                </p>
            </div>
            <form method="POST" action="{{ route('super-admin.bandwidth.optimize') }}">
                @csrf
                <button type="submit" @disabled(! $gdOk || $pending === 0)
                        class="{{ $btn }} bg-electric hover:brightness-110 text-white disabled:opacity-40 disabled:cursor-not-allowed">
                    Optimalkan gambar{{ $pending > 0 ? ' (' . $pending . ')' : '' }}
                </button>
            </form>
        </div>

        @unless($gdOk)
            <p class="mb-4 p-3 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300 text-xs">Ekstensi PHP "gd" belum aktif di hosting, jadi gambar belum bisa dikecilkan. Aktifkan lewat pengaturan PHP di panel hosting.</p>
        @endunless

        <div class="grid grid-cols-2 gap-3 mb-4">
            @foreach($uploads['dirs'] as $dir => $info)
                <div class="p-3 rounded-2xl bg-white/[0.03] border border-white/10">
                    <p class="text-[11px] text-slate-500">{{ $dirLabels[$dir] ?? $dir }}</p>
                    <p class="text-white font-semibold">{{ $fmt($info['bytes']) }}</p>
                    <p class="text-[11px] text-slate-500">{{ $info['files'] }} file</p>
                </div>
            @endforeach
        </div>

        @if(count($uploads['top']))
            <p class="text-xs font-semibold text-slate-400 mb-2">File terbesar</p>
            <ul class="space-y-1.5">
                @foreach($uploads['top'] as $f)
                    <li class="flex items-center justify-between gap-3 text-xs">
                        <span class="text-slate-300 truncate">{{ $f['path'] }}</span>
                        <span class="shrink-0 font-semibold {{ $f['bytes'] > 500 * 1024 ? 'text-amber-300' : 'text-slate-400' }}">{{ $fmt($f['bytes']) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <p class="mt-4 text-[11px] text-slate-500 leading-relaxed">
            Banner diperkecil maksimal 1600 px lalu disimpan {{ $webpOk ? 'WebP' : 'JPEG' }}; foto akun diperkecil maksimal 1600 px (watermark tetap ada).
            Diproses 12 gambar per klik supaya hosting tidak timeout, ulangi sampai angka di tombol jadi 0.
            Upload baru otomatis sudah dikecilkan.
        </p>
    </div>

    {{-- 2b. File yatim --}}
    <div class="glass-panel rounded-3xl p-5 sm:p-6 {{ $orphans['ok'] && $orphans['count'] > 0 ? 'border-amber-500/30' : '' }}">
        <p class="font-display font-bold text-white text-lg">File yatim (sisa akun &amp; banner yang dihapus)</p>
        <p class="text-xs text-slate-400 mt-1 mb-4">
            File di folder uploads yang sudah tidak dipakai database lagi. Menghapusnya membebaskan ruang hosting dan tidak mengubah tampilan website.
            Yang dicek: foto akun, banner, gambar maintenance, dan QRIS, termasuk thumbnail dan salinan lamanya.
        </p>

        @if(! $orphans['ok'])
            <p class="p-3 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300 text-xs leading-relaxed">{{ $orphans['error'] }}</p>
        @elseif($orphans['count'] === 0)
            <p class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs">
                Tidak ada file yatim. Semua file di folder uploads masih dipakai ({{ number_format($orphans['referenced']) }} gambar terdaftar di database).
            </p>
        @else
            <form method="POST" action="{{ route('super-admin.bandwidth.orphans') }}"
                  onsubmit="return confirm('Hapus file yang dicentang secara permanen? Tindakan ini tidak bisa dibatalkan.')">
                @csrf

                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <p class="text-sm text-white">
                        <span class="font-semibold">{{ number_format($orphans['count']) }} file</span>
                        <span class="text-slate-400">&middot; {{ $fmt($orphans['bytes']) }} bisa dibebaskan</span>
                    </p>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" checked
                               onchange="var on=this.checked;document.querySelectorAll('.orphan-cb').forEach(function(c){c.checked=on;});">
                        Pilih semua
                    </label>
                </div>

                <ul class="space-y-1.5 max-h-72 overflow-y-auto pr-1 mb-4">
                    @foreach(array_slice($orphans['files'], 0, 500) as $f)
                        <li>
                            <label class="flex items-center gap-3 p-2.5 rounded-xl bg-white/[0.03] border border-white/10 cursor-pointer text-xs">
                                <input type="checkbox" name="paths[]" value="{{ $f['path'] }}" class="orphan-cb shrink-0" checked>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-slate-200 truncate">{{ $f['path'] }}</span>
                                    <span class="block text-[11px] text-slate-500">
                                        {{ $dirLabels[$f['dir']] ?? $f['dir'] }}
                                        &middot; {{ \Carbon\Carbon::createFromTimestamp($f['mtime'], 'Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                        @if($f['legacy']) &middot; ada salinan lama @endif
                                        @if($f['thumbs'] > 0) &middot; {{ $f['thumbs'] }} thumbnail @endif
                                    </span>
                                </span>
                                <a href="{{ upload_url($f['path']) }}" target="_blank" rel="noopener"
                                   class="shrink-0 text-[11px] text-electric hover:underline" onclick="event.stopPropagation()">lihat</a>
                                <span class="shrink-0 font-semibold text-slate-400">{{ $fmt($f['bytes']) }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>

                @if($orphans['count'] > 500)
                    <p class="mb-3 text-[11px] text-amber-300">Hanya 500 file pertama yang ditampilkan. Setelah dihapus, buka halaman ini lagi untuk sisanya.</p>
                @endif

                <button type="submit" class="{{ $btn }} bg-red-500 hover:bg-red-400 text-white">
                    Hapus file yang dicentang
                </button>
            </form>
        @endif

        <p class="mt-4 text-[11px] text-slate-500 leading-relaxed">
            Aman: file yang diunggah dalam 1 jam terakhir tidak ikut dihitung{{ $orphans['recent'] > 0 ? ' (' . $orphans['recent'] . ' file dilewati sekarang)' : '' }},
            dan sebelum menghapus daftar dicek ulang ke database. Pemindaian otomatis berhenti bila database dan folder uploads tampak tidak cocok.
            Lewat terminal: <span class="text-slate-300">php artisan uploads:clean-orphans --dry-run</span>
        </p>
    </div>

    {{-- 3. Cache & sampah --}}
    <div class="glass-panel rounded-3xl p-5 sm:p-6">
        <p class="font-display font-bold text-white text-lg">Bersihkan cache &amp; data sampah</p>
        <p class="text-xs text-slate-400 mt-1 mb-4">Tidak membuat siapa pun logout dan tidak menghapus transaksi, akun, booking, maupun foto.</p>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
            <div class="p-3 rounded-2xl bg-white/[0.03] border border-white/10">
                <p class="text-[11px] text-slate-500">Cache aplikasi</p>
                <p class="text-white font-semibold">{{ number_format($junk['cache_rows']) }} baris</p>
            </div>
            <div class="p-3 rounded-2xl bg-white/[0.03] border border-white/10">
                <p class="text-[11px] text-slate-500">Cache tampilan</p>
                <p class="text-white font-semibold">{{ $fmt($junk['view_bytes']) }}</p>
            </div>
            <div class="p-3 rounded-2xl bg-white/[0.03] border border-white/10">
                <p class="text-[11px] text-slate-500">File log</p>
                <p class="text-white font-semibold">{{ $fmt($junk['log_bytes']) }}</p>
            </div>
            <div class="p-3 rounded-2xl bg-white/[0.03] border border-white/10">
                <p class="text-[11px] text-slate-500">Data pengunjung</p>
                <p class="text-white font-semibold">{{ number_format($junk['visitor_rows']) }} baris</p>
            </div>
            <div class="p-3 rounded-2xl bg-white/[0.03] border border-white/10">
                <p class="text-[11px] text-slate-500">Notifikasi lama</p>
                <p class="text-white font-semibold">{{ number_format($junk['notif_old']) }} baris</p>
            </div>
        </div>

        <form method="POST" action="{{ route('super-admin.bandwidth.clean') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label for="visitor_days" class="block text-xs font-semibold text-slate-400 mb-2">Hapus data pengunjung lebih tua dari</label>
                <select id="visitor_days" name="visitor_days"
                        class="bg-white/5 border border-white/10 rounded-2xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-electric">
                    @foreach([7, 14, 30, 60, 90] as $d)
                        <option value="{{ $d }}" class="text-slate-900" @selected($d === 30)>{{ $d }} hari</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="{{ $btn }} bg-white/10 hover:bg-white/15 border border-white/10 text-white">
                Bersihkan sekarang
            </button>
        </form>
        <p class="mt-3 text-[11px] text-slate-500">Sekaligus memperbarui .htaccess folder uploads agar browser pengunjung menyimpan gambar selama 1 tahun (tidak diunduh ulang tiap kunjungan).</p>
    </div>

    {{-- 4. Tips di luar kode --}}
    <div class="glass-panel rounded-3xl p-5 sm:p-6">
        <p class="font-display font-bold text-white text-lg mb-2">Yang paling besar efeknya (di luar website)</p>
        <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-400 leading-relaxed">
            <li><span class="text-slate-200">Pasang Cloudflare (gratis)</span> di depan domain. Gambar dilayani dari server Cloudflare, jadi tidak lagi memakan kuota hosting. Ini biasanya memotong bandwidth paling banyak.</li>
            <li>Jangan membiarkan tab Dashboard Super Admin terbuka semalaman di PC/HP.</li>
            <li>Setelah kuota reset, cek pemakaian per hari di panel hosting untuk melihat apakah ada lonjakan (bot / scraper).</li>
        </ul>
    </div>
</div>
@endsection