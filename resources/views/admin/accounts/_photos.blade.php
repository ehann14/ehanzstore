@php
    $existing = $account ? $account->images : collect();
    $maxPhotos = 10;
@endphp

<div class="space-y-4" id="photoSection" data-max="{{ $maxPhotos }}" data-existing="{{ $existing->count() }}">
    <div class="flex items-end justify-between border-b border-white/5 pb-2">
        <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider">Foto Akun</h3>
        <span class="text-xs text-slate-500"><span id="photoCount">{{ $existing->count() }}</span>/{{ $maxPhotos }} foto</span>
    </div>

    {{-- Urutan foto BARU yang dipilih jadi foto utama (diisi script). Kosong = ikut aturan bawaan. --}}
    <input type="hidden" name="primary_new" id="primaryNew" value="">

    <p class="text-[11px] text-slate-500">
        Foto <span class="font-semibold text-slate-300">Utama</span> tampil paling depan di katalog.
        Klik <span class="font-semibold text-slate-300">Jadikan utama</span> pada foto mana pun, termasuk foto yang baru dipilih sebelum disimpan.
        Bila foto utama dihapus, foto pertama yang tersisa otomatis jadi utama.
        @if($account && $existing->count() > 1)<br><span class="font-semibold text-slate-300">Ubah urutan galeri:</span> tahan ikon titik-titik di tengah atas foto, lalu geser. Urutan langsung tersimpan.@endif
        @if($account)<br>Tombol &quot;Jadikan utama&quot; pada foto yang sudah tersimpan langsung diproses, jadi simpan dulu perubahan lain sebelum menekannya.@endif
    </p>

    @if($existing->isNotEmpty())
        {{-- Toolbar: pilih semua + hapus terpilih --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-midnight/60 px-3 py-2">
            <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-slate-300 font-medium">
                <input type="checkbox" id="selectAllPhotos" style="width:16px;height:16px;accent-color:#ef4444;cursor:pointer">
                <span>Pilih semua</span>
                <span id="selectedInfo" class="text-slate-500"></span>
            </label>
            <button type="submit" form="bulkDeleteImages" id="bulkDeleteBtn" disabled
                    class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition"
                    style="opacity:.4;cursor:not-allowed">
                Hapus terpilih (<span id="bulkCount">0</span>)
            </button>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3" id="existingGrid" data-reorder-url="{{ route('admin.accounts.images.reorder', $account) }}">
            @foreach($existing as $img)
                <div class="photo-item relative aspect-square rounded-xl overflow-hidden border border-white/10 bg-midnight group cursor-pointer" data-id="{{ $img->id }}">
                    <img src="{{ thumb_url($img->image_path, 320) }}" alt="" class="w-full h-full object-cover" draggable="false">
                    <button type="button" class="drag-handle absolute top-1 left-1/2 -translate-x-1/2 w-8 h-7 rounded-lg bg-black/70 hover:bg-electric text-white flex items-center justify-center transition"
                            style="touch-action:none;cursor:grab;user-select:none;-webkit-user-select:none"
                            title="Tahan lalu geser untuk mengubah urutan" aria-label="Geser untuk mengubah urutan foto">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>
                    </button>
                    <span class="order-no absolute bottom-1 right-1 min-w-[18px] text-center px-1 rounded bg-black/70 text-[10px] font-bold text-slate-200">{{ $loop->iteration }}</span>
                    <input type="checkbox" name="image_ids[]" value="{{ $img->id }}" form="bulkDeleteImages"
                           class="photo-check absolute top-1 left-1"
                           style="width:20px;height:20px;accent-color:#ef4444;cursor:pointer">
                    @if($img->is_primary)
                        <span class="existing-primary-badge absolute bottom-1 left-1 px-1.5 py-0.5 rounded bg-electric text-[10px] font-bold text-white">Utama</span>
                    @else
                        <button type="submit" form="primaryImg{{ $img->id }}" title="Jadikan foto utama"
                                class="absolute bottom-1 left-1 px-1.5 py-0.5 rounded bg-black/70 hover:bg-electric text-[10px] font-bold text-white transition">
                            Jadikan utama
                        </button>
                    @endif
                    <button type="submit" form="deleteImg{{ $img->id }}" title="Hapus foto"
                            onclick="return confirm('Hapus foto ini?')"
                            class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/70 hover:bg-red-600 text-white flex items-center justify-center transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endforeach
        </div>
        <p id="orderStatus" class="text-xs min-h-[1rem]"></p>
    @endif

    <div class="border-2 border-dashed border-white/10 rounded-2xl p-6 text-center hover:border-brand-500/50 transition-colors bg-brand-950/30">
        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" id="photoInput" class="hidden">
        <label for="photoInput" class="cursor-pointer flex flex-col items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-brand-500/10 flex items-center justify-center text-brand-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <p class="text-sm text-slate-300 font-medium">Klik untuk {{ $account ? 'tambah' : 'upload' }} foto</p>
            <p class="text-xs text-slate-500">Maksimal {{ $maxPhotos }} foto (JPG, PNG, WebP). Foto besar otomatis diperkecil.</p>
        </label>
    </div>

    <p id="photoStatus" class="text-xs text-slate-400 hidden"></p>
    <div id="previewContainer" class="grid grid-cols-3 sm:grid-cols-5 gap-3"></div>

    @error('images') <p class="text-red-400 text-xs">{{ $message }}</p> @enderror
    @foreach($errors->get('images.*') as $msgs)
        @foreach($msgs as $m)<p class="text-red-400 text-xs">{{ $m }}</p>@endforeach
    @endforeach
</div>

@if($account)
    {{-- Form hapus foto di luar form utama (form tidak boleh bersarang) --}}
    @push('outside-forms')
        {{-- Form hapus banyak foto sekaligus (checkbox foto terhubung lewat atribut form="bulkDeleteImages") --}}
        <form id="bulkDeleteImages" action="{{ route('admin.accounts.images.bulk-destroy', $account) }}" method="POST" class="hidden">
            @csrf @method('DELETE')
        </form>
        @foreach($existing as $img)
            <form id="deleteImg{{ $img->id }}" action="{{ route('admin.accounts.images.destroy', [$account, $img]) }}" method="POST" class="hidden">
                @csrf @method('DELETE')
            </form>
            @unless($img->is_primary)
                <form id="primaryImg{{ $img->id }}" action="{{ route('admin.accounts.images.primary', [$account, $img]) }}" method="POST" class="hidden">
                    @csrf
                </form>
            @endunless
        @endforeach
    @endpush
@endif

@push('scripts')
<script>
(function () {
    const MAX_SIDE = 1600;         // sisi terpanjang setelah diperkecil (px)
    const QUALITY  = 0.82;         // kualitas JPEG
    const SKIP_UNDER = 700 * 1024; // file kecil tidak perlu dikompres

    const section   = document.getElementById('photoSection');
    const input     = document.getElementById('photoInput');
    const preview   = document.getElementById('previewContainer');
    const statusEl  = document.getElementById('photoStatus');
    const countEl   = document.getElementById('photoCount');
    const form      = document.getElementById('accountForm');
    const submitBtn = document.getElementById('submitBtn');
    const max       = parseInt(section.dataset.max, 10);
    const existing  = parseInt(section.dataset.existing, 10);
    let busy = false;

    const fmt = b => (b / 1024 / 1024).toFixed(2) + ' MB';

    // ---- Pilihan foto utama untuk foto BARU ----
    const primaryInput = document.getElementById('primaryNew');
    let primaryNew = null;   // urutan foto baru yang dipilih jadi utama (null = tidak dipilih)

    function refreshPrimary() {
        const cards = Array.from(preview.children);
        // Form tambah (belum ada foto lama): foto pertama otomatis jadi utama bila tidak ada pilihan lain
        const effective = primaryNew !== null ? primaryNew : (existing === 0 && cards.length ? 0 : null);
        if (primaryInput) primaryInput.value = primaryNew === null ? '' : String(primaryNew);

        cards.forEach((card, i) => {
            const btn = card.querySelector('.primary-btn');
            if (!btn) return;
            const isPrimary = effective === i;
            btn.textContent = isPrimary ? 'Utama' : 'Jadikan utama';
            btn.className = 'primary-btn absolute bottom-1 left-1 px-1.5 py-0.5 rounded text-[10px] font-bold text-white transition '
                + (isPrimary ? 'bg-electric' : 'bg-black/70 hover:bg-electric');
            card.style.outline = isPrimary ? '3px solid #3b82f6' : '';
            card.style.outlineOffset = '-3px';
        });

        // Foto utama lama tidak lagi berlabel "Utama" selama foto baru dipilih menggantikannya
        document.querySelectorAll('.existing-primary-badge').forEach(b => b.classList.toggle('hidden', primaryNew !== null));
    }

    function renderPreview(files) {
        preview.innerHTML = '';
        files.forEach((f, i) => {
            const d = document.createElement('div');
            d.className = 'aspect-square rounded-xl overflow-hidden border border-white/10 relative bg-midnight';

            const img = document.createElement('img');
            img.className = 'w-full h-full object-cover';
            d.appendChild(img);

            const size = document.createElement('span');
            size.className = 'absolute bottom-1 right-1 px-1.5 py-0.5 rounded bg-black/70 text-[10px] text-slate-200';
            size.textContent = fmt(f.size);
            d.appendChild(size);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'primary-btn';
            btn.addEventListener('click', () => {
                // Form edit: klik lagi pada foto yang sudah dipilih = batal. Form tambah: foto pertama selalu ada yang utama.
                primaryNew = (primaryNew === i && existing > 0) ? null : i;
                refreshPrimary();
            });
            d.appendChild(btn);

            preview.appendChild(d);

            // Kartu dibuat berurutan di atas, gambar menyusul saat selesai dibaca (urutan tidak tertukar)
            const r = new FileReader();
            r.onload = ev => { img.src = ev.target.result; };
            r.readAsDataURL(f);
        });
        refreshPrimary();
    }

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
                ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);   // PNG transparan -> latar putih
                ctx.drawImage(img, 0, 0, w, h);
                canvas.toBlob(blob => {
                    if (!blob || blob.size >= file.size) return resolve(file);
                    const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', QUALITY);
            };
            img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    }

    input.addEventListener('change', async function () {
        const allowed = Math.max(0, max - existing);
        let files = Array.from(input.files);
        preview.innerHTML = '';
        statusEl.classList.add('hidden');
        primaryNew = null;
        if (!files.length) { countEl.textContent = existing; refreshPrimary(); return; }

        let note = '';
        if (files.length > allowed) {
            files = files.slice(0, allowed);
            note = ` Hanya ${allowed} foto pertama yang dipakai (batas ${max}).`;
        }

        busy = true;
        if (submitBtn) submitBtn.disabled = true;
        statusEl.classList.remove('hidden');
        statusEl.textContent = 'Memproses foto...';

        const out = [];
        let before = 0, after = 0;
        for (const f of files) {
            const c = await compress(f);
            before += f.size; after += c.size;
            out.push(c);
        }

        const dt = new DataTransfer();
        out.forEach(f => dt.items.add(f));
        input.files = dt.files;

        renderPreview(out);

        countEl.textContent = existing + out.length;
        statusEl.textContent = `${out.length} foto siap diunggah (${fmt(before)} → ${fmt(after)}).` + note;
        busy = false;
        if (submitBtn) submitBtn.disabled = false;
    });

    // ---- Pilih semua + hapus terpilih ----
    const selectAll = document.getElementById('selectAllPhotos');
    const bulkBtn   = document.getElementById('bulkDeleteBtn');
    const bulkCount = document.getElementById('bulkCount');
    const infoEl    = document.getElementById('selectedInfo');
    const checks    = Array.from(document.querySelectorAll('.photo-check'));

    function refreshSelection() {
        const n = checks.filter(c => c.checked).length;
        checks.forEach(c => {
            const box = c.closest('.photo-item');
            if (box) box.style.outline = c.checked ? '3px solid #ef4444' : '';
            if (box) box.style.outlineOffset = '-3px';
        });
        if (bulkCount) bulkCount.textContent = n;
        if (infoEl) infoEl.textContent = n ? `(${n} dipilih)` : '';
        if (bulkBtn) {
            bulkBtn.disabled = n === 0;
            bulkBtn.style.opacity = n === 0 ? '.4' : '1';
            bulkBtn.style.cursor = n === 0 ? 'not-allowed' : 'pointer';
        }
        if (selectAll) {
            selectAll.checked = n > 0 && n === checks.length;
            selectAll.indeterminate = n > 0 && n < checks.length;
        }
    }

    if (selectAll) selectAll.addEventListener('change', () => {
        checks.forEach(c => c.checked = selectAll.checked);
        refreshSelection();
    });

    checks.forEach(c => c.addEventListener('change', refreshSelection));

    // Klik di foto = centang/lepas centang (kecuali klik tombol X atau checkbox-nya)
    document.querySelectorAll('.photo-item').forEach(item => {
        item.addEventListener('click', e => {
            if (e.target.closest('button') || e.target.classList.contains('photo-check')) return;
            const c = item.querySelector('.photo-check');
            if (c) { c.checked = !c.checked; refreshSelection(); }
        });
    });

    if (bulkBtn) bulkBtn.addEventListener('click', e => {
        const n = checks.filter(c => c.checked).length;
        if (!n || !confirm(`Hapus ${n} foto terpilih? Tindakan ini tidak bisa dibatalkan.`)) e.preventDefault();
    });

    refreshSelection();

    // ---- Geser (drag) untuk mengubah urutan foto yang sudah tersimpan ----
    const grid        = document.getElementById('existingGrid');
    const orderStatus = document.getElementById('orderStatus');

    if (grid && grid.dataset.reorderUrl) {
        const tokenInput = form ? form.querySelector('input[name="_token"]') : null;
        let dragging = null;
        let changed  = false;
        let saveTimer = null;

        const items = () => Array.from(grid.querySelectorAll(':scope > .photo-item'));

        function renumber() {
            items().forEach((el, i) => {
                const n = el.querySelector('.order-no');
                if (n) n.textContent = i + 1;
            });
        }

        function say(msg, kind) {
            if (!orderStatus) return;
            orderStatus.textContent = msg;
            orderStatus.className = 'text-xs min-h-[1rem] ' + (kind === 'err' ? 'text-red-400' : kind === 'ok' ? 'text-emerald-400' : 'text-slate-400');
        }

        function save() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(async () => {
                say('Menyimpan urutan...', 'info');
                try {
                    const res = await fetch(grid.dataset.reorderUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': tokenInput ? tokenInput.value : '',
                        },
                        body: JSON.stringify({ order: items().map(el => parseInt(el.dataset.id, 10)) }),
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    say('Urutan foto tersimpan.', 'ok');
                } catch (err) {
                    say('Urutan gagal disimpan. Muat ulang halaman lalu coba lagi.', 'err');
                }
            }, 250);
        }

        // Pindahkan foto ke posisi baru TANPA memindahkan elemen yang sedang digeser
        // (yang dipindah adalah foto lainnya), supaya pointer yang sedang menahan tidak terlepas.
        function moveTo(item, newIndex) {
            const list = items();
            const from = list.indexOf(item);
            if (from === -1 || newIndex === from || newIndex < 0 || newIndex >= list.length) return false;

            const desired = list.filter(el => el !== item);
            desired.splice(newIndex, 0, item);

            for (let i = 0; i < newIndex; i++) grid.insertBefore(desired[i], item);
            let prev = item;
            for (let i = newIndex + 1; i < desired.length; i++) {
                grid.insertBefore(desired[i], prev.nextSibling);
                prev = desired[i];
            }
            return true;
        }

        function finish() {
            if (!dragging) return;
            dragging.style.opacity = '';
            dragging.style.transform = '';
            dragging.style.zIndex = '';
            dragging = null;
            if (changed) { changed = false; renumber(); save(); }
        }

        grid.addEventListener('pointerdown', e => {
            const handle = e.target.closest('.drag-handle');
            if (!handle || (e.pointerType === 'mouse' && e.button !== 0)) return;
            dragging = handle.closest('.photo-item');
            changed = false;
            try { handle.setPointerCapture(e.pointerId); } catch (_) {}
            dragging.style.opacity = '.65';
            dragging.style.transform = 'scale(1.05)';
            dragging.style.zIndex = '5';
            e.preventDefault();
        });

        window.addEventListener('pointermove', e => {
            if (!dragging) return;
            const over   = document.elementFromPoint(e.clientX, e.clientY);
            const target = over ? over.closest('.photo-item') : null;
            if (!target || target === dragging || target.parentElement !== grid) return;
            if (moveTo(dragging, items().indexOf(target))) { changed = true; renumber(); }
        });

        window.addEventListener('pointerup', finish);
        window.addEventListener('pointercancel', finish);

        // Cadangan tanpa mouse / layar sentuh: fokus ke ikon geser lalu tekan tombol panah
        grid.addEventListener('keydown', e => {
            const handle = e.target.closest('.drag-handle');
            if (!handle) return;
            const item = handle.closest('.photo-item');
            const idx  = items().indexOf(item);
            let target = null;
            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') target = idx - 1;
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') target = idx + 1;
            if (target === null) return;
            e.preventDefault();
            if (moveTo(item, target)) { renumber(); save(); handle.focus(); }
        });
    }

    if (form) form.addEventListener('submit', e => {
        if (busy) { e.preventDefault(); return; }
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Menyimpan...'; }
    });
})();
</script>
@endpush