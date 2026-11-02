@extends('layouts.app')

@section('title', 'Titip Akun - EHANZSTORE.ID')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
@php
    $inputCls   = 'w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:border-electric focus:ring-1 focus:ring-electric outline-none transition';
    $labelCls   = 'block text-xs font-semibold text-slate-400 mb-2';
    $card       = 'rounded-3xl border border-white/10 bg-white/[0.03] p-5 sm:p-7 space-y-5';
    $heroes     = [
        'barbarian_king_level' => 'Barbarian King',
        'archer_queen_level'   => 'Archer Queen',
        'minion_prince_level'  => 'Minion Prince',
        'grand_warden_level'   => 'Grand Warden',
        'royal_champion_level' => 'Royal Champion',
        'dragon_duke_level'    => 'Dragon Duke',
    ];
@endphp

<div class="max-w-2xl mx-auto px-4 pt-32 pb-20">

@if($state === 'closed')
    <div class="text-center rounded-3xl border border-white/10 bg-white/[0.03] p-8 sm:p-12">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-500/15 text-slate-300 flex items-center justify-center mb-5">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4"/></svg>
        </div>
        <h1 class="font-display font-extrabold text-2xl text-white mb-3">Link ini sudah tidak aktif</h1>
        <p class="text-slate-400 leading-relaxed">Link titip akun hanya bisa dipakai satu kali dan punya batas waktu. Kalau datamu belum terkirim, minta link baru ke admin EHANZSTORE.</p>
    </div>

@elseif($state === 'done')
    <div class="text-center rounded-3xl border border-emerald-500/30 bg-emerald-500/5 p-8 sm:p-12">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-500/15 text-emerald-300 flex items-center justify-center mb-5">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="font-display font-extrabold text-2xl text-white mb-3">Data akunmu sudah terkirim</h1>
        <p class="text-slate-300 leading-relaxed">Terima kasih! Admin akan memeriksa data dan screenshot akunmu, lalu menghubungi kamu lewat WhatsApp sebelum akun ditayangkan di katalog.</p>
        <p class="text-slate-500 text-sm mt-4">Link tadi sudah ditutup dan tidak bisa dibuka lagi. Kamu boleh menutup halaman ini.</p>
    </div>

@else
    <div class="mb-8 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-bold tracking-wide mb-5">Titip Jual</div>
        <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-white mb-3">Titip Akun untuk Dijual</h1>
        <p class="text-slate-400">Isi data dan screenshot akunmu di sini. Setelah dikirim, link ini ditutup dan admin yang menerbitkannya.</p>
        <p class="text-slate-500 text-xs mt-2">Untuk: <span class="text-slate-300 font-semibold">{{ $account->name }}</span></p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            <p class="font-semibold mb-1">Data belum bisa dikirim:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form id="sellerForm" action="{{ route('seller.submit', $token) }}" method="POST" enctype="multipart/form-data" class="space-y-6" novalidate>
        @csrf
        {{-- jebakan bot: manusia tidak melihat kolom ini --}}
        <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true">
            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        {{-- 1. Data kamu --}}
        <section class="{{ $card }}">
            <h2 class="font-display font-bold text-lg text-white">1. Data kamu</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $labelCls }}">Nama kamu <span class="text-red-400">*</span></label>
                    <input type="text" name="consignor_name" value="{{ old('consignor_name') }}" maxlength="100" placeholder="Nama / nama panggilan" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Nomor WhatsApp <span class="text-red-400">*</span></label>
                    <input type="tel" name="consignor_phone" value="{{ old('consignor_phone') }}" maxlength="25" inputmode="tel" placeholder="0812-3456-7890" class="{{ $inputCls }}">
                </div>
            </div>
            <div>
                <label class="{{ $labelCls }}">Harga yang kamu inginkan (Rp) <span class="text-red-400">*</span></label>
                <input type="number" name="price" value="{{ old('price') }}" min="1000" max="999999999" inputmode="numeric" placeholder="Contoh: 500000" class="{{ $inputCls }}">
                <p class="text-[11px] text-slate-500 mt-1.5">Komisi toko hanya diambil saat akun laku, besarnya disepakati dengan admin lewat WhatsApp.</p>
            </div>
        </section>

        {{-- 2. Data akun --}}
        <section class="{{ $card }}">
            <h2 class="font-display font-bold text-lg text-white">2. Data akun</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $labelCls }}">Level Town Hall <span class="text-red-400">*</span></label>
                    <input type="number" name="town_hall" value="{{ old('town_hall') }}" min="1" max="18" inputmode="numeric" placeholder="Contoh: 14" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Player Tag <span class="text-slate-600 font-normal">(opsional)</span></label>
                    <input type="text" name="player_tag" value="{{ old('player_tag') }}" maxlength="20" placeholder="#P2LQ8Y0" class="{{ $inputCls }} uppercase">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Level akun <span class="text-slate-600 font-normal">(opsional)</span></label>
                    <input type="number" name="account_level" value="{{ old('account_level') }}" min="0" max="500" inputmode="numeric" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Jumlah Builder <span class="text-slate-600 font-normal">(opsional)</span></label>
                    <input type="number" name="builder_count" value="{{ old('builder_count', 5) }}" min="0" max="6" inputmode="numeric" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Gems <span class="text-slate-600 font-normal">(opsional)</span></label>
                    <input type="number" name="gems" value="{{ old('gems') }}" min="0" inputmode="numeric" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Info tambahan <span class="text-slate-600 font-normal">(opsional)</span></label>
                    <input type="text" name="cn_information" value="{{ old('cn_information') }}" maxlength="255" placeholder="Contoh: akun lama, skin langka" class="{{ $inputCls }}">
                </div>
            </div>

            <div>
                <p class="{{ $labelCls }}">Level hero <span class="text-slate-600 font-normal">(kosongkan kalau belum punya)</span></p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($heroes as $field => $label)
                        <div>
                            <label class="block text-[11px] text-slate-500 mb-1">{{ $label }}</label>
                            <input type="number" name="{{ $field }}" value="{{ old($field) }}" min="0" max="100" inputmode="numeric" class="{{ $inputCls }} !py-2.5">
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="{{ $labelCls }}">Deskripsi / catatan <span class="text-slate-600 font-normal">(opsional)</span></label>
                <textarea name="description" rows="4" maxlength="3000" placeholder="Kelebihan akun, skin, pet, base, dan hal lain yang perlu diketahui pembeli..." class="{{ $inputCls }} resize-none">{{ old('description') }}</textarea>
            </div>
        </section>

        {{-- 3. Syarat wajib --}}
        <section class="{{ $card }}">
            <h2 class="font-display font-bold text-lg text-white">3. Syarat wajib</h2>

            <div>
                <p class="{{ $labelCls }}">Proteksi akun (verifikasi nomor HP) <span class="text-red-400">*</span></p>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="protection_status" value="Unprotected" class="peer sr-only" @checked(old('protection_status', 'Unprotected') === 'Unprotected')>
                        <span class="block text-center rounded-xl border border-white/10 py-3 text-sm font-semibold text-slate-300 peer-checked:border-electric peer-checked:bg-electric/15 peer-checked:text-white transition">Proteksi OFF</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="protection_status" value="Protected" class="peer sr-only" @checked(old('protection_status') === 'Protected')>
                        <span class="block text-center rounded-xl border border-white/10 py-3 text-sm font-semibold text-slate-300 peer-checked:border-electric peer-checked:bg-electric/15 peer-checked:text-white transition">Proteksi ON</span>
                    </label>
                </div>
            </div>

            <label class="flex items-start gap-3 cursor-pointer rounded-2xl border border-amber-500/30 bg-amber-500/5 p-4">
                <input type="checkbox" name="consignor_email_ok" value="1" @checked(old('consignor_email_ok')) class="mt-1 w-4 h-4 rounded border-white/20 bg-white/5 text-electric focus:ring-electric">
                <span>
                    <span class="block text-sm font-bold text-amber-200">Gmail akun bisa diganti ke Gmail pembeli <span class="text-red-400">*</span></span>
                    <span class="block text-xs text-slate-400 mt-0.5">Saya sudah memastikan Gmail akun ini SUDAH bisa diganti ke Gmail milik pembeli. Akun yang Gmail-nya tidak bisa diganti tidak boleh dititip.</span>
                </span>
            </label>

            <label id="phoneOkBox" class="flex items-start gap-3 cursor-pointer rounded-2xl border border-amber-500/30 bg-amber-500/5 p-4">
                <input type="checkbox" name="consignor_phone_ok" value="1" @checked(old('consignor_phone_ok')) class="mt-1 w-4 h-4 rounded border-white/20 bg-white/5 text-electric focus:ring-electric">
                <span>
                    <span class="block text-sm font-bold text-amber-200">Nomor proteksi bisa diganti ke nomor pembeli <span class="text-red-400">*</span></span>
                    <span class="block text-xs text-slate-400 mt-0.5">Wajib dicentang kalau proteksi ON. Kalau proteksi OFF, abaikan.</span>
                </span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="agree" value="1" @checked(old('agree')) class="mt-1 w-4 h-4 rounded border-white/20 bg-white/5 text-electric focus:ring-electric">
                <span class="text-sm text-slate-300">Saya sudah membaca dan menyetujui <a href="{{ route('consignment.terms') }}" target="_blank" rel="noopener" class="text-electric hover:underline">Syarat Titip Akun</a>. <span class="text-red-400">*</span></span>
            </label>
        </section>

        {{-- 4. Screenshot --}}
        <section class="{{ $card }}" id="photoSection" data-max="10">
            <div class="flex items-end justify-between">
                <h2 class="font-display font-bold text-lg text-white">4. Screenshot akun <span class="text-red-400">*</span></h2>
                <span class="text-xs text-slate-500"><span id="photoCount">0</span>/10 foto</span>
            </div>
            <p class="text-xs text-slate-400 -mt-2">Unggah screenshot yang jelas: base, hero, lab, skin, dan profil. Minimal 1 foto, maksimal 10. Foto otomatis diperkecil.</p>
            <label for="photoInput" class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-white/15 hover:border-electric/60 bg-white/[0.02] py-8 cursor-pointer transition">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-sm font-semibold text-slate-300">Ketuk untuk pilih foto</span>
            </label>
            <input id="photoInput" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
            <p id="photoStatus" class="hidden text-xs text-slate-400"></p>
            <div id="previewContainer" class="grid grid-cols-3 sm:grid-cols-5 gap-3"></div>
        </section>

        <button type="submit" id="submitBtn"
                class="w-full bg-electric hover:bg-blue-500 text-white font-bold py-4 rounded-2xl transition shadow-lg shadow-electric/20 disabled:opacity-50 disabled:cursor-not-allowed">
            Kirim Data Akun
        </button>
        <p class="text-center text-[11px] text-slate-500">Setelah dikirim, link ini langsung ditutup dan tidak bisa dibuka lagi.</p>
    </form>
@endif
</div>
@endsection

@push('scripts')
@if($state === 'form')
<script>
(function () {
    const MAX_SIDE = 1600, QUALITY = 0.82, SKIP_UNDER = 700 * 1024;
    const section = document.getElementById('photoSection');
    const input   = document.getElementById('photoInput');
    const preview = document.getElementById('previewContainer');
    const statusEl = document.getElementById('photoStatus');
    const countEl = document.getElementById('photoCount');
    const form    = document.getElementById('sellerForm');
    const submitBtn = document.getElementById('submitBtn');
    const max = parseInt(section.dataset.max, 10);
    let busy = false;
    const fmt = b => (b / 1024 / 1024).toFixed(2) + ' MB';

    function compress(file) {
        return new Promise(resolve => {
            if (file.size <= SKIP_UNDER && file.type !== 'image/png') return resolve(file);
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                URL.revokeObjectURL(url);
                const scale = Math.min(1, MAX_SIDE / Math.max(img.width, img.height));
                const w = Math.round(img.width * scale), h = Math.round(img.height * scale);
                const canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
                ctx.drawImage(img, 0, 0, w, h);
                canvas.toBlob(blob => {
                    if (!blob || blob.size >= file.size) return resolve(file);
                    resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', QUALITY);
            };
            img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    }

    input.addEventListener('change', async function () {
        let files = Array.from(input.files);
        preview.innerHTML = '';
        statusEl.classList.add('hidden');
        if (!files.length) { countEl.textContent = 0; return; }

        let note = '';
        if (files.length > max) { files = files.slice(0, max); note = ` Hanya ${max} foto pertama yang dipakai.`; }

        busy = true; submitBtn.disabled = true;
        statusEl.classList.remove('hidden'); statusEl.textContent = 'Memproses foto...';

        const out = []; let before = 0, after = 0;
        for (const f of files) { const c = await compress(f); before += f.size; after += c.size; out.push(c); }

        const dt = new DataTransfer();
        out.forEach(f => dt.items.add(f));
        input.files = dt.files;

        out.forEach(f => {
            const r = new FileReader();
            r.onload = ev => {
                const d = document.createElement('div');
                d.className = 'aspect-square rounded-xl overflow-hidden border border-white/10 relative bg-black/30';
                d.innerHTML = '<img src="' + ev.target.result + '" class="w-full h-full object-cover" alt="">'
                    + '<span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded bg-black/70 text-[10px] text-slate-200">' + fmt(f.size) + '</span>';
                preview.appendChild(d);
            };
            r.readAsDataURL(f);
        });

        countEl.textContent = out.length;
        statusEl.textContent = `${out.length} foto siap dikirim (${fmt(before)} → ${fmt(after)}).` + note;
        busy = false; submitBtn.disabled = false;
    });

    // Cegah kirim dua kali (link sekali pakai) dan kirim saat foto masih diproses
    form.addEventListener('submit', function (e) {
        if (busy) { e.preventDefault(); return; }
        if (!input.files.length) {
            e.preventDefault();
            statusEl.classList.remove('hidden');
            statusEl.textContent = 'Unggah minimal 1 screenshot akun dulu.';
            statusEl.className = 'text-xs text-red-400';
            section.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Mengirim...';
    });

    // Kotak "nomor proteksi" hanya relevan bila proteksi ON
    const phoneBox = document.getElementById('phoneOkBox');
    const radios = form.querySelectorAll('input[name="protection_status"]');
    function syncPhoneBox() {
        const on = form.querySelector('input[name="protection_status"]:checked')?.value === 'Protected';
        phoneBox.style.opacity = on ? '1' : '0.45';
    }
    radios.forEach(r => r.addEventListener('change', syncPhoneBox));
    syncPhoneBox();
})();
</script>
@endif
@endpush