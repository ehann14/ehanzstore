{{--
    Field Harga Coret (diskon) + penanda Hot Deal.
    Variabel: $account (CocAccount|null)
    Harga di kolom "Harga (Rp)" = harga jual sekarang (setelah diskon).
    Harga Coret = harga normal sebelum diskon, harus lebih besar dari harga jual. Kosongkan jika tidak ada diskon.
--}}
@php
    $origVal = old('original_price', $account && $account->original_price ? (int) $account->original_price : '');
    $hotVal  = (bool) old('is_hot_deal', $account ? $account->is_hot_deal : false);
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
    })();
</script>
@endpush
@endonce