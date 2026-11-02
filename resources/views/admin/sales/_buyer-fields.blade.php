{{--
    Field data pembeli + pilihan durasi garansi (7 / 14 / 21 hari) + preview.
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
    // Titip jual: akun titipan (CocAccount) yang sedang dijual / transaksinya diubah. Null = akun biasa.
    $consign      = $consignAccount ?? null;
    $isConsign    = $consign !== null || ($sale && $sale->is_consignment);
    $warrantyOptions = \App\Models\Sale::WARRANTY_OPTIONS;
    $warrantyTiers   = \App\Models\Sale::warrantyTiers($isConsign);
    $savedFee        = $sale ? (int) $sale->warranty_fee : 0;
    $warrantyDays    = (int) old('warranty_days', $sale ? $sale->warrantyDays() : \App\Models\Sale::WARRANTY_DAYS);
    if (! in_array($warrantyDays, $warrantyOptions, true)) {
        $warrantyDays = \App\Models\Sale::WARRANTY_DAYS;
    }

    $consignPaid  = $sale && $sale->is_consignment && $sale->consignorIsPaid();
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

    @if($consign)
        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4" data-consign-info
             data-type="{{ $consign->commission_type === 'fixed' ? 'fixed' : 'percent' }}" data-value="{{ (float) $consign->commission_value }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-200">Akun titipan</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Pemilik: <span class="text-white font-medium">{{ $consign->consignor_name }}</span>
                        @if($consign->consignorLocalPhone()) &middot; {{ $consign->consignorLocalPhone() }} @endif
                        &middot; Komisi toko {{ $consign->commissionLabel() }}
                    </p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-300 text-[11px] font-bold border border-amber-500/30">TITIP</span>
            </div>
            <div class="mt-3 grid sm:grid-cols-2 gap-2 text-sm">
                <p class="text-slate-400">Komisi toko: <span id="{{ $idPrefix }}_commission" class="font-bold text-emerald-400">-</span></p>
                <p class="text-slate-400">Setor ke pemilik: <span id="{{ $idPrefix }}_payout" class="font-bold text-white">-</span></p>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">Komisi dihitung dari harga jual akhir di luar biaya Garansi Plus (kalau ada nego, bagian pemilik ikut menyesuaikan). Status setoran dicatat di menu Titip Jual.</p>
            @if($consignPaid)
                <p class="text-[11px] text-amber-300 mt-2">Setoran ke pemilik sudah ditandai lunas, jadi harga jual dikunci. Batalkan tanda lunas di menu Titip Jual bila perlu mengubahnya.</p>
            @endif
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label for="{{ $idPrefix }}_cost" class="{{ $labelCls }}">{{ $consign ? 'Bagian Pemilik / Modal (Rp)' : 'Harga Modal (Rp)' }} @unless($consign)<span class="text-slate-600 font-normal">(opsional)</span>@endunless</label>
            <input id="{{ $idPrefix }}_cost" type="number" name="cost_price" min="0" max="999999999" step="1"
                   value="{{ $costVal }}" placeholder="0" @if($consign) readonly @endif
                   class="{{ $inputCls }} @if($consign) opacity-60 cursor-not-allowed @endif @error('cost_price') border-red-500 @enderror">
            <p class="text-[11px] text-slate-500 mt-1">{{ $consign ? 'Otomatis = harga jual - komisi.' : 'Terisi dari modal akun.' }} {{ $consign ? 'Komisi (laba)' : 'Laba' }}: <span id="{{ $idPrefix }}_profit" class="font-bold text-slate-300">-</span></p>
            @error('cost_price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="hidden md:block"></div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label for="{{ $idPrefix }}_price" class="{{ $labelCls }}">Harga Jual (Rp) <span class="text-red-400">*</span></label>
            <input id="{{ $idPrefix }}_price" type="number" name="price" required min="0" max="999999999" step="1"
                   value="{{ $priceVal }}" placeholder="0" @if($consignPaid) readonly @endif
                   class="{{ $inputCls }} @if($consignPaid) opacity-60 cursor-not-allowed @endif @error('price') border-red-500 @enderror">
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

        <!-- Pilihan durasi garansi -->
        <div class="mt-4">
            <span class="{{ $labelCls }}">Durasi Garansi <span class="text-red-400">*</span></span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2" role="radiogroup" aria-label="Durasi garansi">
                @foreach($warrantyOptions as $opt)
                    @php($tier = $warrantyTiers[$opt] ?? ['short' => '', 'label' => ''])
                    <label class="cursor-pointer">
                        <input type="radio" name="warranty_days" value="{{ $opt }}" required
                               data-fee="{{ \App\Models\Sale::warrantyFeeFor($opt, $isConsign) }}"
                               class="peer sr-only" @checked($warrantyDays === $opt)>
                        <span class="block h-full text-center rounded-xl border border-white/10 bg-brand-950/50 px-3 py-3 transition
                                     hover:bg-white/5 text-slate-300
                                     peer-checked:border-emerald-500/60 peer-checked:bg-emerald-500/15 peer-checked:text-emerald-300
                                     peer-disabled:opacity-40 peer-disabled:cursor-not-allowed
                                     peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">
                            <span class="block text-base font-bold">{{ $opt }} hari</span>
                            <span class="block text-[11px] opacity-80 leading-snug mt-0.5">{{ $tier['label'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @if($isConsign)
                <p class="text-[11px] text-slate-500 mt-2">Akun titipan: garansi 14 hari (Rp {{ number_format(\App\Models\Sale::warrantyFeeFor(14, true), 0, ',', '.') }}) dan 21 hari (Rp {{ number_format(\App\Models\Sale::warrantyFeeFor(21, true), 0, ',', '.') }}) dibayar pembeli ke admin EHANZSTORE. Biaya otomatis menambah harga jual, 100% milik toko, tidak mengurangi bagian pemilik.</p>
            @else
                <p class="text-[11px] text-slate-500 mt-2">Pilih sesuai kondisi Gmail saat serah terima. Garansi Plus 21 hari menambah harga jual otomatis sebesar biayanya.</p>
            @endif
            @error('warranty_days') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Preview garansi -->
        <div class="mt-3 flex items-start gap-3 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
            <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <div class="text-sm">
                <p class="text-emerald-300 font-semibold">Garansi <span id="{{ $idPrefix }}_warranty_days_label">{{ $warrantyDays }}</span> hari dihitung dari waktu serah terima</p>
                <p id="{{ $idPrefix }}_warranty_tier" class="text-slate-400 text-xs mt-0.5">{{ $warrantyTiers[$warrantyDays]['desc'] ?? '' }}</p>
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
        const DEFAULT_DAYS = {{ $warrantyDays }};
        const TIER_DESC = @json(collect($warrantyTiers)->map(fn ($t) => $t['desc'])->all());
        const tierOut = document.getElementById('{{ $idPrefix }}_warranty_tier');
        const scope  = document.querySelector('[data-buyer-fields="{{ $idPrefix }}"]');
        const input  = document.getElementById('{{ $idPrefix }}_handover');
        const output = document.getElementById('{{ $idPrefix }}_warranty_end');
        const label  = document.getElementById('{{ $idPrefix }}_warranty_days_label');
        if (!scope || !input || !output) return;

        // Durasi garansi yang sedang dipilih (7 / 14 / 21).
        function selectedDays() {
            const checked = scope.querySelector('input[name="warranty_days"]:checked');
            return checked ? parseInt(checked.value, 10) : DEFAULT_DAYS;
        }

        const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const pad    = n => String(n).padStart(2, '0');

        function render() {
            const days = selectedDays();
            if (label) label.textContent = days;
            if (tierOut && TIER_DESC[days]) tierOut.textContent = TIER_DESC[days];

            // Nilai datetime-local sudah dalam WIB (tanpa zona), jadi cukup tambah N hari secara kalender.
            const m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(input.value);
            if (!m) { output.textContent = '-'; return; }
            const d = new Date(+m[1], +m[2] - 1, +m[3] + days, +m[4], +m[5]);
            output.textContent = pad(d.getDate()) + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear()
                + ', ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ' WIB';
        }

        input.addEventListener('input', render);
        scope.querySelectorAll('input[name="warranty_days"]').forEach(r => r.addEventListener('change', render));
        render();
    })();

    // Laba = harga jual - modal, dihitung langsung saat diketik.
    // Akun titipan: modal = bagian pemilik (harga jual - komisi), jadi laba = komisi.
    (function () {
        const price  = document.getElementById('{{ $idPrefix }}_price');
        const cost   = document.getElementById('{{ $idPrefix }}_cost');
        const output = document.getElementById('{{ $idPrefix }}_profit');
        if (!price || !cost || !output) return;

        const rp = n => (n < 0 ? '-Rp ' : 'Rp ') + Math.abs(Math.round(n)).toLocaleString('id-ID');

        // Biaya Garansi Plus (21 hari): otomatis ditambahkan ke harga jual, dan dikurangi lagi bila durasi diganti.
        const scopeEl   = document.querySelector('[data-buyer-fields="{{ $idPrefix }}"]');
        const radios    = scopeEl ? scopeEl.querySelectorAll('input[name="warranty_days"]') : [];
        const currentFee = () => {
            const c = scopeEl ? scopeEl.querySelector('input[name="warranty_days"]:checked') : null;
            return c ? (parseInt(c.dataset.fee, 10) || 0) : 0;
        };
        let appliedFee = {{ $savedFee }};

        if (price.readOnly) {
            // Harga dikunci (setoran pemilik sudah lunas): durasi dengan biaya berbeda tidak boleh dipilih.
            radios.forEach(r => { if ((parseInt(r.dataset.fee, 10) || 0) !== appliedFee) r.disabled = true; });
        }

        function syncWarrantyFee() {
            const want = currentFee();
            if (want === appliedFee) return;
            if (!price.readOnly) {
                price.value = Math.max(0, (parseFloat(price.value) || 0) + want - appliedFee);
            }
            appliedFee = want;
        }
        radios.forEach(r => r.addEventListener('change', () => { syncWarrantyFee(); render(); }));

        const info    = document.querySelector('[data-buyer-fields="{{ $idPrefix }}"] [data-consign-info]');
        const outCom  = document.getElementById('{{ $idPrefix }}_commission');
        const outPay  = document.getElementById('{{ $idPrefix }}_payout');

        function syncConsignment() {
            if (!info) return;
            const p = Math.max(0, (parseFloat(price.value) || 0) - currentFee());
            const v = parseFloat(info.dataset.value) || 0;
            const c = info.dataset.type === 'fixed' ? Math.min(v, p) : Math.round(p * v / 100);
            const payout = Math.max(0, p - c);
            if (outCom) outCom.textContent = rp(c);
            if (outPay) outPay.textContent = rp(payout);
            cost.value = Math.round(payout);
        }

        function render() {
            syncConsignment();
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