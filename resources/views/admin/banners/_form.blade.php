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

    {{-- Jadwal tayang: banner muncul dan hilang sendiri sesuai tanggal (WIB), mirip masa berlaku Hot Deal --}}
    @php
        $startVal = old('starts_at', $b ? $b->scheduleInputValue('starts_at') : '');
        $endVal   = old('ends_at',   $b ? $b->scheduleInputValue('ends_at')   : '');
    @endphp
    <div data-schedule-box class="rounded-2xl border border-white/10 bg-white/[0.02] p-4 space-y-4">
        <div>
            <p class="text-sm font-semibold text-white">Jadwal Tayang <span class="text-slate-600 font-normal text-xs">(opsional)</span></p>
            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                Kosongkan keduanya agar banner tampil terus selama "Tampilkan di beranda" dicentang.
                Waktu dalam WIB. Di luar jadwal, banner otomatis tidak tampil tanpa perlu dihapus.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label for="starts_at" class="block text-xs font-semibold text-slate-400 mb-2">Mulai Tampil (WIB)</label>
                <input type="datetime-local" id="starts_at" name="starts_at" value="{{ $startVal }}"
                       class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition [color-scheme:dark] @error('starts_at') border-red-500 @enderror">
                @error('starts_at') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                <div class="flex flex-wrap gap-2 mt-2">
                    <button type="button" data-start-now
                            class="px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-[11px] font-semibold text-slate-300 transition">Sekarang</button>
                    <button type="button" data-start-clear
                            class="px-3 py-1.5 rounded-lg border border-white/10 text-[11px] font-semibold text-slate-500 hover:text-white hover:bg-white/5 transition">Kosongkan</button>
                </div>
            </div>

            <div>
                <label for="ends_at" class="block text-xs font-semibold text-slate-400 mb-2">Berhenti Tampil (WIB)</label>
                <input type="datetime-local" id="ends_at" name="ends_at" value="{{ $endVal }}"
                       class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-brand-500 transition [color-scheme:dark] @error('ends_at') border-red-500 @enderror">
                @error('ends_at') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach([3 => '+3 hari', 7 => '+7 hari', 30 => '+30 hari'] as $days => $label)
                        <button type="button" data-end-preset="{{ $days }}"
                                class="px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-[11px] font-semibold text-slate-300 transition">{{ $label }}</button>
                    @endforeach
                    <button type="button" data-end-clear
                            class="px-3 py-1.5 rounded-lg border border-white/10 text-[11px] font-semibold text-slate-500 hover:text-white hover:bg-white/5 transition">Kosongkan</button>
                </div>
                <p class="text-[11px] text-slate-600 mt-2">Tombol +hari dihitung dari tanggal mulai (atau dari sekarang bila mulai kosong).</p>
            </div>
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
    <script>
        (function () {
            var box = document.querySelector('[data-schedule-box]');
            if (!box) return;

            var start = box.querySelector('input[name="starts_at"]');
            var end   = box.querySelector('input[name="ends_at"]');

            // Sekarang + N hari dalam WIB (tidak bergantung zona waktu perangkat)
            var fmt = new Intl.DateTimeFormat('en-CA', {
                timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', hour12: false
            });
            function wib(ms) {
                var p = {};
                fmt.formatToParts(new Date(ms)).forEach(function (x) { p[x.type] = x.value; });
                return p.year + '-' + p.month + '-' + p.day + 'T' + (p.hour === '24' ? '00' : p.hour) + ':' + p.minute;
            }
            // Nilai datetime-local (dianggap WIB) -> milidetik. Dipakai sebagai titik awal tombol +hari.
            function baseMs() {
                if (start.value) {
                    var t = Date.parse(start.value + ':00+07:00');
                    if (!isNaN(t)) return t;
                }
                return Date.now();
            }

            box.querySelector('[data-start-now]').addEventListener('click', function () { start.value = wib(Date.now()); });
            box.querySelector('[data-start-clear]').addEventListener('click', function () { start.value = ''; });
            box.querySelector('[data-end-clear]').addEventListener('click', function () { end.value = ''; });
            box.querySelectorAll('[data-end-preset]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    end.value = wib(baseMs() + Number(btn.dataset.endPreset) * 86400000);
                });
            });
        })();
    </script>
    {{-- Alpine dipakai untuk form ini (layout admin tidak memuatnya secara global) --}}
    <script src="//unpkg.com/alpinejs" defer></script>
@endpush