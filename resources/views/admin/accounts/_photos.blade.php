@php
    $existing = $account ? $account->images : collect();
    $maxPhotos = 8;
@endphp

<div class="space-y-4" id="photoSection" data-max="{{ $maxPhotos }}" data-existing="{{ $existing->count() }}">
    <div class="flex items-end justify-between border-b border-white/5 pb-2">
        <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider">Foto Akun</h3>
        <span class="text-xs text-slate-500"><span id="photoCount">{{ $existing->count() }}</span>/{{ $maxPhotos }} foto</span>
    </div>

    @if($existing->isNotEmpty())
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
            @foreach($existing as $img)
                <div class="relative aspect-square rounded-xl overflow-hidden border border-white/10 bg-midnight group">
                    <img src="{{ $img->url }}" alt="" class="w-full h-full object-cover">
                    @if($img->is_primary)
                        <span class="absolute bottom-1 left-1 px-1.5 py-0.5 rounded bg-electric text-[10px] font-bold text-white">Utama</span>
                    @endif
                    <button type="submit" form="deleteImg{{ $img->id }}" title="Hapus foto"
                            onclick="return confirm('Hapus foto ini?')"
                            class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/70 hover:bg-red-600 text-white flex items-center justify-center transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endforeach
        </div>
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
        @foreach($existing as $img)
            <form id="deleteImg{{ $img->id }}" action="{{ route('admin.accounts.images.destroy', [$account, $img]) }}" method="POST" class="hidden">
                @csrf @method('DELETE')
            </form>
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
        if (!files.length) { countEl.textContent = existing; return; }

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

        out.forEach(f => {
            const r = new FileReader();
            r.onload = ev => {
                const d = document.createElement('div');
                d.className = 'aspect-square rounded-xl overflow-hidden border border-white/10 relative bg-midnight';
                d.innerHTML = `<img src="${ev.target.result}" class="w-full h-full object-cover">
                    <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded bg-black/70 text-[10px] text-slate-200">${fmt(f.size)}</span>`;
                preview.appendChild(d);
            };
            r.readAsDataURL(f);
        });

        countEl.textContent = existing + out.length;
        statusEl.textContent = `${out.length} foto siap diunggah (${fmt(before)} → ${fmt(after)}).` + note;
        busy = false;
        if (submitBtn) submitBtn.disabled = false;
    });

    if (form) form.addEventListener('submit', e => {
        if (busy) { e.preventDefault(); return; }
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Menyimpan...'; }
    });
})();
</script>
@endpush