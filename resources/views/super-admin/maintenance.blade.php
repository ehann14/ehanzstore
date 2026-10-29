@extends('layouts.admin')

@section('title', 'Mode Maintenance')

@section('content')
@php
    $fieldClass = 'w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric';
    $isOn = old('active', $settings['active'] ? '1' : '0') == '1';
    $hasImage = $settings['image_url'] !== '';
@endphp

<div class="max-w-3xl mx-auto pb-16">
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Mode Maintenance</h1>
        <p class="text-sm text-slate-400 mt-1">Saat aktif, pengunjung umum hanya melihat halaman maintenance khusus (bukan pop up). Admin yang sudah login tetap bisa membuka website seperti biasa.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="glass-panel rounded-3xl p-5 sm:p-6 mb-6 flex flex-wrap items-center gap-4 {{ $settings['active'] ? 'border-amber-500/30' : '' }}">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $settings['active'] ? 'bg-amber-500/15 text-amber-300' : 'bg-emerald-500/15 text-emerald-300' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div class="flex-1 min-w-[200px]">
            <p class="font-display font-bold text-white text-lg">
                Status sekarang:
                <span class="{{ $settings['active'] ? 'text-amber-300' : 'text-emerald-300' }}">{{ $settings['active'] ? 'MAINTENANCE AKTIF' : 'Website normal' }}</span>
            </p>
            <p class="text-xs text-slate-400 mt-0.5">
                {{ $settings['active'] ? 'Pengunjung umum melihat halaman maintenance (HTTP 503).' : 'Semua pengunjung bisa membuka website.' }}
            </p>
        </div>
        <a href="{{ route('super-admin.maintenance.preview') }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-white/10 bg-white/5 hover:bg-white/10 text-sm font-semibold text-white transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Lihat halaman maintenance
        </a>
    </div>

    <form method="POST" action="{{ route('super-admin.maintenance.update') }}" enctype="multipart/form-data" class="glass-panel rounded-3xl p-6 md:p-8 space-y-6">
        @csrf

        <label class="flex items-start gap-4 p-4 rounded-2xl border border-white/10 bg-white/[0.03] cursor-pointer has-[:checked]:border-amber-500/40 has-[:checked]:bg-amber-500/5 transition">
            <input type="checkbox" name="active" value="1" @checked($isOn) class="mt-1 w-5 h-5 rounded accent-amber-500">
            <span>
                <span class="block font-semibold text-white">Aktifkan mode maintenance</span>
                <span class="block text-xs text-slate-400 mt-0.5">Centang lalu simpan untuk menutup website sementara. Hilangkan centang untuk membukanya lagi.</span>
            </span>
        </label>

        <div>
            <label for="m_title" class="block text-xs font-semibold text-slate-400 mb-2">Judul halaman</label>
            <input id="m_title" type="text" name="title" maxlength="80" value="{{ old('title', $settings['title']) }}" class="{{ $fieldClass }}">
        </div>

        <div>
            <label for="m_message" class="block text-xs font-semibold text-slate-400 mb-2">Pesan untuk pengunjung</label>
            <textarea id="m_message" name="message" rows="4" maxlength="500" class="{{ $fieldClass }}">{{ old('message', $settings['message']) }}</textarea>
        </div>

        {{-- Gambar halaman maintenance --}}
        <div>
            <label for="m_image" class="block text-xs font-semibold text-slate-400 mb-2">Gambar maintenance <span class="text-slate-600 font-normal">(opsional)</span></label>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 space-y-4">
                <div class="flex items-center justify-center rounded-xl bg-slate-950/60 border border-white/5 min-h-[140px] p-3">
                    <img id="m_image_preview"
                         src="{{ $settings['image_url'] }}"
                         alt="Gambar maintenance saat ini"
                         class="{{ $hasImage ? '' : 'hidden' }} max-h-52 w-auto max-w-full rounded-xl object-contain">
                    <p id="m_image_empty" class="{{ $hasImage ? 'hidden' : '' }} text-xs text-slate-500 text-center px-4">
                        Belum ada gambar kustom. Halaman maintenance memakai ilustrasi bawaan (jendela browser, roda gigi, dan kunci inggris).
                    </p>
                </div>

                <input id="m_image" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-slate-300 file:mr-4 file:py-2.5 file:px-5 file:rounded-full file:border-0 file:bg-electric file:text-white file:font-semibold file:cursor-pointer hover:file:bg-blue-500">

                <p class="text-[11px] text-slate-500 leading-relaxed">
                    JPG, PNG, atau WebP, maksimal 3 MB. Ukuran ideal sekitar 800 x 500 px (landscape). Gambar ditampilkan di atas judul pada halaman maintenance.
                </p>

                @if($hasImage)
                    <label class="flex items-center gap-3 text-sm text-slate-300 cursor-pointer">
                        <input type="checkbox" name="remove_image" value="1" id="m_remove" class="w-4 h-4 rounded accent-red-500">
                        Hapus gambar kustom (kembali ke ilustrasi bawaan)
                    </label>
                @endif
            </div>
        </div>

        <div>
            <label for="m_until_date" class="block text-xs font-semibold text-slate-400 mb-2">Perkiraan selesai <span class="text-slate-600 font-normal">(opsional, WIB, format 24 jam)</span></label>
            @php
                $selHour   = old('until_hour', $untilHour);
                $selMinute = old('until_minute', $untilMinute);
            @endphp
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex-1 min-w-[10rem]">
                    <input id="m_until_date" type="date" name="until_date" value="{{ old('until_date', $untilDate) }}" aria-label="Tanggal" class="{{ $fieldClass }} [color-scheme:dark]">
                </div>
                <x-time-picker hour-name="until_hour" minute-name="until_minute" :hour="$selHour" :minute="$selMinute"
                               :step="5" label="Pilih jam perkiraan selesai" />
                <span class="text-xs font-semibold text-slate-400">WIB</span>
            </div>
            @if($untilLabel)
                <p class="text-[11px] text-slate-400 mt-2">Tersimpan saat ini: <span class="text-white font-semibold">{{ $untilLabel }}</span></p>
            @endif
            <p class="text-[11px] text-slate-500 mt-1.5">Jika diisi, halaman maintenance menampilkan hitung mundur. Kosongkan tanggal untuk menghapus perkiraan selesai. Mode tidak mati otomatis saat waktunya lewat; matikan manual setelah selesai.</p>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3 pt-4 border-t border-white/5">
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="text-center text-sm text-slate-400 hover:text-white transition px-4 py-3">Lihat website</a>
            <button type="submit" class="sm:ml-auto bg-electric hover:bg-blue-500 text-white font-bold py-3 px-8 rounded-full transition shadow-lg shadow-electric/20 active:scale-95">
                Simpan Pengaturan
            </button>
        </div>
    </form>

    <div class="mt-6 rounded-2xl border border-white/5 bg-white/[0.02] p-5 text-xs text-slate-500 leading-relaxed">
        <p class="font-semibold text-slate-400 mb-1">Catatan</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>Halaman <span class="font-mono">/login</span> dan panel admin tetap bisa dibuka pengunjung agar kamu tidak terkunci dari sistem.</li>
            <li>Admin yang sedang login melihat website normal, jadi kamu bisa mengecek hasil perbaikan sebelum membukanya untuk umum. Untuk melihat tampilan yang dilihat pengunjung, pakai tombol <b class="text-slate-400">Lihat halaman maintenance</b> (simpan dulu supaya perubahan ikut tampil).</li>
            <li>Kunjungan ke halaman maintenance tidak dihitung di analitik.</li>
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var input   = document.getElementById('m_image');
        var preview = document.getElementById('m_image_preview');
        var empty   = document.getElementById('m_image_empty');
        var remove  = document.getElementById('m_remove');
        var saved   = preview ? preview.getAttribute('src') : '';
        if (!input || !preview) { return; }

        // Pratinjau gambar yang baru dipilih sebelum disimpan
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];

            if (!file) {
                preview.src = saved;
                preview.classList.toggle('hidden', !saved);
                if (empty) { empty.classList.toggle('hidden', !!saved); }
                return;
            }

            if (file.size > 3 * 1024 * 1024) {
                alert('Ukuran gambar maksimal 3 MB. Pilih gambar yang lebih kecil.');
                input.value = '';
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            if (empty) { empty.classList.add('hidden'); }
            if (remove) { remove.checked = false; }
        });

        // Mencentang "hapus" mengosongkan pilihan file
        if (remove) {
            remove.addEventListener('change', function () {
                if (remove.checked) { input.value = ''; preview.src = saved; }
                preview.style.opacity = remove.checked ? '.35' : '';
            });
        }
    })();
</script>
@endpush