{{--
    Field Harga Modal + estimasi laba langsung (harga jual - modal).
    Variabel: $account (CocAccount|null)
    Hanya tampil di area admin, tidak pernah dipakai di halaman publik.
--}}
@php
    $costVal = old('cost_price', $account ? (int) $account->cost_price : 0);
@endphp
<div data-cost-box>
    <label class="block text-xs font-semibold text-slate-400 mb-2">Harga Modal (Rp) <span class="text-slate-600 font-normal">(opsional, hanya terlihat admin)</span></label>
    <input type="number" name="cost_price" value="{{ $costVal }}" min="0" max="999999999" step="1" placeholder="0"
           class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition @error('cost_price') border-red-500 @enderror">
    @error('cost_price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    <p class="text-[11px] text-slate-500 mt-1.5">
        Estimasi laba jika terjual sesuai harga listing:
        <span data-profit-out class="font-bold text-slate-300">-</span>
        <span data-margin-out class="text-slate-500"></span>
    </p>
</div>

@once
@push('scripts')
<script>
    (function () {
        const box = document.querySelector('[data-cost-box]');
        if (!box) return;
        const form  = box.closest('form');
        const price = form.querySelector('input[name="price"]');
        const cost  = form.querySelector('input[name="cost_price"]');
        const out   = box.querySelector('[data-profit-out]');
        const pct   = box.querySelector('[data-margin-out]');
        const rp    = n => (n < 0 ? '-Rp ' : 'Rp ') + Math.abs(Math.round(n)).toLocaleString('id-ID');

        function render() {
            const p = parseFloat(price.value) || 0;
            const c = parseFloat(cost.value) || 0;
            const profit = p - c;
            out.textContent = rp(profit);
            out.className = 'font-bold ' + (profit < 0 ? 'text-red-400' : (profit > 0 ? 'text-emerald-400' : 'text-slate-300'));
            pct.textContent = p > 0 ? '(' + ((profit / p) * 100).toFixed(1).replace('.', ',') + '%)' : '';
        }

        price.addEventListener('input', render);
        cost.addEventListener('input', render);
        render();
    })();
</script>
@endpush
@endonce