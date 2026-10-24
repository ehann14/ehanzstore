{{--
    Field data pembeli + preview garansi 7 hari.
    Variabel:
      $sale        (opsional) transaksi yang sedang dilengkapi, null jika penjualan baru
      $defaultPrice (opsional) harga awal jika belum ada old()/transaksi
      $idPrefix    (opsional) awalan id elemen supaya tidak bentrok jika dipakai 2x
--}}
@php
    $idPrefix     = $idPrefix ?? 'buyer';
    $sale         = $sale ?? null;
    $inputCls     = 'w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition';
    $labelCls     = 'block text-xs font-semibold text-slate-400 mb-2';
    $nowWib       = now(\App\Models\Sale::DISPLAY_TZ);
    $handoverVal  = old('handover_at', $sale ? $sale->handoverInputValue() : $nowWib->format('Y-m-d\TH:i'));
    $priceVal     = old('price', $sale ? (int) $sale->sale_price : (int) ($defaultPrice ?? 0));
    // Modal transaksi; bila masih 0 (data lama), pakai modal yang tersimpan di akunnya.
    $savedCost    = $sale ? ((float) $sale->cost_price > 0 ? (int) $sale->cost_price : (int) ($sale->account?->cost_price ?? 0)) : (int) ($defaultCost ?? 0);
    $costVal      = old('cost_price', $savedCost);
    $methodVal    = old('payment_method', $sale?->payment_method);
    $warrantyDays = \App\Models\Sale::WARRANTY_DAYS;
@endphp

<div class="space-y-4" data-buyer-fields="{{ $idPrefix }}">
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label for="{{ $idPrefix }}_name" class="{{ $labelCls }}">Nama Pembeli <span class="text-red-400">*</span></label>
            <input id="{{ $idPrefix }}_name" type="text" name="buyer_name" required minlength="2" maxlength="100"
                   value="{{ old('buyer_name', $sale?->buyer_name) }}" placeholder="Nama lengkap / nama panggilan"
                   class="{{ $inputCls }} @error('buyer_name') border-red-500 @enderror">
            @error('buyer_name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="{{ $idPrefix }}_phone" class="{{ $labelCls }}">No. WhatsApp Pembeli <span class="text-red-400">*</span></label>
            <input id="{{ $idPrefix }}_phone" type="tel" name="buyer_phone" required inputmode="tel" maxlength="25"
                   value="{{ old('buyer_phone', $sale?->localPhone()) }}" placeholder="0812-3456-7890"
                   class="{{ $inputCls }} @error('buyer_phone') border-red-500 @enderror">
            <p class="text-[11px] text-slate-500 mt-1">Bisa ditulis 08xx, +62 8xx, atau 8xx. Dipakai untuk klaim garansi.</p>
            @error('buyer_phone') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label for="{{ $idPrefix }}_cost" class="{{ $labelCls }}">Harga Modal (Rp) <span class="text-slate-600 font-normal">(opsional)</span></label>
            <input id="{{ $idPrefix }}_cost" type="number" name="cost_price" min="0" max="999999999" step="1"
                   value="{{ $costVal }}" placeholder="0"
                   class="{{ $inputCls }} @error('cost_price') border-red-500 @enderror">
            <p class="text-[11px] text-slate-500 mt-1">Terisi dari modal akun. Laba: <span id="{{ $idPrefix }}_profit" class="font-bold text-slate-300">-</span></p>
            @error('cost_price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="hidden md:block"></div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label for="{{ $idPrefix }}_price" class="{{ $labelCls }}">Harga Jual (Rp) <span class="text-red-400">*</span></label>
            <input id="{{ $idPrefix }}_price" type="number" name="price" required min="0" max="999999999" step="1"
                   value="{{ $priceVal }}" placeholder="0"
                   class="{{ $inputCls }} @error('price') border-red-500 @enderror">
            <p class="text-[11px] text-slate-500 mt-1">Terisi dari harga akun, ubah jika ada nego.</p>
            @error('price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="{{ $idPrefix }}_method" class="{{ $labelCls }}">Metode Pembayaran <span class="text-slate-600 font-normal">(opsional)</span></label>
            <select id="{{ $idPrefix }}_method" name="payment_method" class="{{ $inputCls }} @error('payment_method') border-red-500 @enderror">
                <option value="" class="bg-slate-900">Pilih metode&hellip;</option>
                @foreach(\App\Models\Sale::PAYMENT_METHODS as $method)
                    <option value="{{ $method }}" class="bg-slate-900" @selected($methodVal === $method)>{{ $method }}</option>
                @endforeach
            </select>
            @error('payment_method') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="{{ $idPrefix }}_handover" class="{{ $labelCls }}">Waktu Serah Terima (WIB) <span class="text-red-400">*</span></label>
        <input id="{{ $idPrefix }}_handover" type="datetime-local" name="handover_at" required
               max="{{ $nowWib->clone()->addMinutes(5)->format('Y-m-d\TH:i') }}" value="{{ $handoverVal }}"
               class="{{ $inputCls }} @error('handover_at') border-red-500 @enderror">
        @error('handover_at') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror

        <!-- Preview garansi -->
        <div class="mt-3 flex items-start gap-3 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
            <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <div class="text-sm">
                <p class="text-emerald-300 font-semibold">Garansi {{ $warrantyDays }} hari dihitung dari waktu serah terima</p>
                <p class="text-slate-400 text-xs mt-0.5">Garansi berakhir: <span id="{{ $idPrefix }}_warranty_end" class="text-white font-medium">-</span></p>
            </div>
        </div>
    </div>

    <div>
        <label for="{{ $idPrefix }}_notes" class="{{ $labelCls }}">Catatan <span class="text-slate-600 font-normal">(opsional)</span></label>
        <textarea id="{{ $idPrefix }}_notes" name="notes" rows="3" maxlength="1000" placeholder="Catatan tambahan transaksi..."
                  class="{{ $inputCls }} resize-none">{{ old('notes', $sale?->notes) }}</textarea>
        @error('notes') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const DAYS   = {{ $warrantyDays }};
        const input  = document.getElementById('{{ $idPrefix }}_handover');
        const output = document.getElementById('{{ $idPrefix }}_warranty_end');
        if (!input || !output) return;

        const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const pad    = n => String(n).padStart(2, '0');

        function render() {
            // Nilai datetime-local sudah dalam WIB (tanpa zona), jadi cukup tambah 7 hari secara kalender.
            const m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(input.value);
            if (!m) { output.textContent = '-'; return; }
            const d = new Date(+m[1], +m[2] - 1, +m[3] + DAYS, +m[4], +m[5]);
            output.textContent = pad(d.getDate()) + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear()
                + ', ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ' WIB';
        }

        input.addEventListener('input', render);
        render();
    })();

    // Laba = harga jual - modal, dihitung langsung saat diketik.
    (function () {
        const price  = document.getElementById('{{ $idPrefix }}_price');
        const cost   = document.getElementById('{{ $idPrefix }}_cost');
        const output = document.getElementById('{{ $idPrefix }}_profit');
        if (!price || !cost || !output) return;

        const rp = n => (n < 0 ? '-Rp ' : 'Rp ') + Math.abs(Math.round(n)).toLocaleString('id-ID');

        function render() {
            const profit = (parseFloat(price.value) || 0) - (parseFloat(cost.value) || 0);
            output.textContent = rp(profit);
            output.className = 'font-bold ' + (profit < 0 ? 'text-red-400' : (profit > 0 ? 'text-emerald-400' : 'text-slate-300'));
        }

        price.addEventListener('input', render);
        cost.addEventListener('input', render);
        cost.addEventListener('change', render);
        price.addEventListener('change', render);
        render();
    })();
</script>
@endpush