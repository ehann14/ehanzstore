{{--
    Field Harga Coret (diskon) + penanda Hot Deal + masa berlaku promo.
    Variabel: $account (CocAccount|null)
    Harga di kolom "Harga (Rp)" = harga jual sekarang (setelah diskon).
    Harga Coret = harga normal sebelum diskon, harus lebih besar dari harga jual. Kosongkan jika tidak ada diskon.
    Tanggal Berakhir (WIB): setelah lewat, diskon & label Hot Deal hilang sendiri dan harga kembali ke harga normal.
--}}
@php
    $origVal = old('original_price', $account && $account->original_price ? (int) $account->original_price : '');
    $hotVal  = (bool) old('is_hot_deal', $account ? $account->is_hot_deal : false);

    // Hanya tampilkan tanggal yang masih di masa depan (yang sudah lewat otomatis dikosongkan)
    $endVal = old('hot_deal_ends_at',
        $account && $account->hot_deal_ends_at && $account->hot_deal_ends_at->isFuture()
            ? $account->hot_deal_ends_at->clone()->timezone(\App\Models\Sale::DISPLAY_TZ)->format('Y-m-d\TH:i')
            : ''
    );
@endphp
<div data-discount-box class="rounded-2xl border border-white/10 bg-white/[0.02] p-4 space-y-4">
    <div>
        <label class="block text-xs font-semibold text-slate-400 mb-2">Harga Coret / Harga Normal (Rp) <span class="text-slate-600 font-normal">(opsional)</span></label>
        <input type="number" name="original_price" value="{{ $origVal }}" min="0" max="999999999" step="1" placeholder="Kosongkan jika tidak ada diskon"
               class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition @error('original_price') border-red-500 @enderror">
        @error('original_price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-500 mt-1.5">
            Isi harga sebelum diskon. Kolom "Harga (Rp)" di atas adalah harga setelah diskon yang dibayar pembeli.
            <span data-discount-out class="font-bold text-slate-300"></span>
        </p>
    </div>

    <label class="flex items-start gap-3 cursor-pointer select-none">
        <input type="hidden" name="is_hot_deal" value="0">
        <input type="checkbox" name="is_hot_deal" value="1" @checked($hotVal)
               class="mt-0.5 w-4 h-4 rounded border-white/20 bg-brand-950/50 text-orange-500 focus:ring-orange-500">
        <span>
            <span class="block text-sm font-semibold text-white">Tandai sebagai Hot Deal</span>
            <span class="block text-[11px] text-slate-500 mt-0.5">Label "Hot Deal" tampil di kartu {{ $account ? $account->noun() : 'akun' }} dan bagian Hot Deal di beranda (hanya selama masih tersedia).</span>
        </span>
    </label>

    <div>
        <label for="hot_deal_ends_at" class="block text-xs font-semibold text-slate-400 mb-2">Promo Berakhir Pada (WIB) <span class="text-slate-600 font-normal">(opsional)</span></label>
        <input type="datetime-local" id="hot_deal_ends_at" name="hot_deal_ends_at" value="{{ $endVal }}"
               class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition [color-scheme:dark] @error('hot_deal_ends_at') border-red-500 @enderror">
        @error('hot_deal_ends_at') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror

        <div class="flex flex-wrap gap-2 mt-2">
            @foreach([1 => '+1 hari', 3 => '+3 hari', 7 => '+7 hari'] as $days => $label)
                <button type="button" data-end-preset="{{ $days }}"
                        class="px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-[11px] font-semibold text-slate-300 transition">{{ $label }}</button>
            @endforeach
            <button type="button" data-end-clear
                    class="px-3 py-1.5 rounded-lg border border-white/10 text-[11px] font-semibold text-slate-500 hover:text-white hover:bg-white/5 transition">Kosongkan</button>
        </div>

        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
            Setelah waktu ini lewat, harga coret dan label Hot Deal hilang sendiri, dan harga jual kembali ke harga normal (harga coret).
            Kosongkan jika promo tanpa batas waktu. Hanya berlaku bila ada harga coret atau Hot Deal dicentang.
        </p>
    </div>
</div>

@once
@push('scripts')
<script>
    (function () {
        const box = document.querySelector('[data-discount-box]');
        if (!box) return;
        const form  = box.closest('form');
        const price = form.querySelector('input[name="price"]');
        const orig  = form.querySelector('input[name="original_price"]');
        const out   = box.querySelector('[data-discount-out]');
        const end   = box.querySelector('input[name="hot_deal_ends_at"]');
        const rp    = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

        function render() {
            const p = parseFloat(price.value);
            const o = parseFloat(orig.value);
            if (!orig.value || isNaN(o) || isNaN(p)) { out.textContent = ''; out.className = 'font-bold text-slate-300'; return; }
            if (o <= p) {
                out.textContent = 'Harga coret harus lebih besar dari harga jual, jadi tidak akan ditampilkan.';
                out.className = 'font-bold text-amber-400';
                return;
            }
            const pct = Math.max(1, Math.round((1 - p / o) * 100));
            out.textContent = 'Tampil diskon ' + pct + '% (hemat ' + rp(o - p) + ').';
            out.className = 'font-bold text-emerald-400';
        }

        price.addEventListener('input', render);
        orig.addEventListener('input', render);
        render();

        // Tombol cepat: sekarang + N hari, dalam WIB (tidak bergantung zona waktu perangkat)
        const fmt = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', hour12: false
        });
        function wib(ms) {
            const p = {};
            fmt.formatToParts(new Date(ms)).forEach(x => { p[x.type] = x.value; });
            return p.year + '-' + p.month + '-' + p.day + 'T' + (p.hour === '24' ? '00' : p.hour) + ':' + p.minute;
        }

        box.querySelectorAll('[data-end-preset]').forEach(btn => {
            btn.addEventListener('click', () => {
                end.value = wib(Date.now() + Number(btn.dataset.endPreset) * 86400000);
            });
        });
        const clear = box.querySelector('[data-end-clear]');
        if (clear) clear.addEventListener('click', () => { end.value = ''; });
    })();
</script>
@endpush
@endonce