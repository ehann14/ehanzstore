@php
    $b = $banner ?? null;
    $linkType = old('link_type', $b->link_type ?? 'whatsapp');
@endphp

<div x-data="{ type: '{{ $linkType }}' }" class="space-y-6">

    <div>
        <label class="block text-xs font-semibold text-slate-400 mb-2">Judul Banner <span class="text-red-400">*</span></label>
        <input type="text" name="title" value="{{ old('title', $b->title ?? '') }}" required maxlength="120"
               placeholder="Contoh: Promo Akhir Bulan"
               class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition">
        <p class="text-[11px] text-slate-500 mt-1">Hanya untuk penanda di admin dan teks alternatif gambar.</p>
        @error('title') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-xs font-semibold text-slate-400 mb-2">
            Gambar Banner @if(!$b)<span class="text-red-400">*</span>@endif
        </label>

        @if($b)
            <img src="{{ $b->image_url }}" alt="" class="w-full max-w-md aspect-[2/1] object-cover rounded-xl border border-white/10 mb-3">
        @endif

        <input type="file" name="image" accept="image/png,image/jpeg,image/webp" {{ $b ? '' : 'required' }}
               class="w-full text-sm text-slate-300 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-electric file:text-white file:font-semibold hover:file:bg-blue-500 bg-brand-950/50 border border-white/10 rounded-xl">
        <p class="text-[11px] text-slate-500 mt-1">
            Disarankan 1920x720 px (rasio 8:3), JPG/PNG/WEBP, maks 4 MB. Taruh teks penting di tengah gambar agar tidak terpotong di HP.
            @if($b) Kosongkan jika tidak ingin mengganti gambar. @endif
        </p>
        @error('image') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-400 mb-2">Tujuan Klik</label>
            <select name="link_type" x-model="type"
                    class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition">
                <option value="whatsapp">WhatsApp</option>
                <option value="instagram">Instagram</option>
                <option value="custom">Link lain</option>
                <option value="none">Tanpa link</option>
            </select>
        </div>

        <div class="md:col-span-2" x-show="type !== 'none'">
            <label class="block text-xs font-semibold text-slate-400 mb-2">
                <span x-show="type === 'whatsapp'">Nomor WhatsApp</span>
                <span x-show="type === 'instagram'" x-cloak>Username Instagram</span>
                <span x-show="type === 'custom'" x-cloak>URL Tujuan</span>
            </label>
            <input type="text" name="link_value" value="{{ old('link_value', $b->link_value ?? '') }}"
                   :placeholder="type === 'whatsapp' ? '082130559126' : (type === 'instagram' ? 'ehanzstore.id' : 'https://...')"
                   class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition">
            @error('link_value') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-400 mb-2">Urutan Tampil</label>
            <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $b->sort_order ?? 0) }}"
                   class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition">
            <p class="text-[11px] text-slate-500 mt-1">Angka kecil tampil lebih dulu.</p>
        </div>

        <div class="md:col-span-2 flex items-end">
            <label class="inline-flex items-center gap-3 cursor-pointer pb-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $b->is_active ?? true) ? 'checked' : '' }}
                       class="w-5 h-5 rounded bg-brand-950 border-white/20 text-electric focus:ring-electric">
                <span class="text-sm text-slate-300">Tampilkan di beranda</span>
            </label>
        </div>
    </div>
</div>

@push('scripts')
    {{-- Alpine dipakai untuk form ini (layout admin tidak memuatnya secara global) --}}
    <script src="//unpkg.com/alpinejs" defer></script>
@endpush