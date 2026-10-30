{{--
    Mode titip jual (konsinyasi): catat pemilik akun dan komisi toko.
    Variabel: $account (CocAccount|null)

    - Dicentang  : harga modal otomatis = bagian pemilik (harga jual - komisi), jadi estimasi laba = komisi.
    - Sudah terjual: data dikunci (angka resminya sudah tersimpan di transaksi).
    Hanya tampil di area admin, tidak pernah dipakai di halaman publik.
--}}
@php
    $account   = $account ?? null;
    $locked    = $account && $account->sale_status === 'sold';
    $inputCls  = 'w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition';
    $labelCls  = 'block text-xs font-semibold text-slate-400 mb-2';

    // Checkbox tidak mengirim nilai saat kosong, jadi old() hanya dipakai bila form baru saja dikirim.
    $isOn      = session()->hasOldInput() ? (bool) old('is_consignment') : (bool) ($account?->is_consignment);
    $typeVal   = old('commission_type', $account?->commission_type ?: 'percent');
    $typeVal   = $typeVal === 'fixed' ? 'fixed' : 'percent';
    $valueRaw  = old('commission_value', ($account && $account->is_consignment) ? (float) $account->commission_value : '');
    $valueVal  = ($valueRaw === '' || $valueRaw === null) ? '' : rtrim(rtrim(number_format((float) $valueRaw, 2, '.', ''), '0'), '.');
@endphp

@if($locked)
    @if($account->isConsignment())
        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-amber-200">Akun titipan</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Pemilik: <span class="text-white font-medium">{{ $account->consignor_name }}</span>
                        @if($account->consignorLocalPhone()) &middot; {{ $account->consignorLocalPhone() }} @endif
                        &middot; Komisi toko {{ $account->commissionLabel() }}
                    </p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-300 text-[11px] font-bold border border-amber-500/30">TITIP</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                Sudah terjual, jadi data titip jual dikunci. Komisi dan bagian pemilik tercatat di transaksinya;
                status setoran ke pemilik diatur di menu <a href="{{ route('admin.consignments.index') }}" class="text-electric hover:underline">Titip Jual</a>.
            </p>
        </div>
    @endif
@else
    <div data-consign-box class="rounded-2xl border border-white/10 bg-white/[0.02] p-4 sm:p-5">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="is_consignment" value="1" data-consign-toggle @checked($isOn)
                   class="mt-1 w-4 h-4 rounded border-white/20 bg-brand-950/50 text-electric focus:ring-electric">
            <span>
                <span class="block text-sm font-bold text-white">Mode titip jual (konsinyasi)</span>
                <span class="block text-xs text-slate-400 mt-0.5">Catat pemilik akun dan komisi Anda. Cocok kalau Anda mau menerima akun dari orang lain.</span>
            </span>
        </label>

        <div data-consign-fields class="{{ $isOn ? '' : 'hidden' }} mt-5 space-y-4 border-t border-white/5 pt-5">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="consignor_name" class="{{ $labelCls }}">Nama Pemilik Akun <span class="text-red-400">*</span></label>
                    <input id="consignor_name" type="text" name="consignor_name" maxlength="100" value="{{ old('consignor_name', $account?->consignor_name) }}"
                           placeholder="Nama pemilik akun" class="{{ $inputCls }} @error('consignor_name') border-red-500 @enderror">
                    @error('consignor_name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="consignor_phone" class="{{ $labelCls }}">No. WhatsApp Pemilik <span class="text-slate-600 font-normal">(opsional)</span></label>
                    <input id="consignor_phone" type="tel" name="consignor_phone" inputmode="tel" maxlength="25" value="{{ old('consignor_phone', $account?->consignorLocalPhone()) }}"
                           placeholder="0812-3456-7890" class="{{ $inputCls }} @error('consignor_phone') border-red-500 @enderror">
                    @error('consignor_phone') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <span class="{{ $labelCls }}">Jenis Komisi Toko <span class="text-red-400">*</span></span>
                    <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Jenis komisi">
                        @foreach(\App\Models\CocAccount::COMMISSION_TYPES as $key => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="commission_type" value="{{ $key }}" class="peer sr-only" @checked($typeVal === $key)>
                                <span class="block text-center rounded-xl border border-white/10 bg-brand-950/50 px-3 py-3 text-sm font-bold text-slate-400 transition
                                             hover:bg-white/5 peer-checked:bg-electric/15 peer-checked:border-electric/60 peer-checked:text-white
                                             peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('commission_type') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="commission_value" class="{{ $labelCls }}">Besar Komisi <span data-consign-unit class="text-slate-500 font-normal">(%)</span> <span class="text-red-400">*</span></label>
                    <input id="commission_value" type="number" name="commission_value" min="0" step="0.01" value="{{ $valueVal }}" placeholder="10"
                           class="{{ $inputCls }} @error('commission_value') border-red-500 @enderror">
                    @error('commission_value') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-xl bg-brand-950/50 border border-white/5 p-3 text-sm grid sm:grid-cols-2 gap-2">
                <p class="text-slate-400">Komisi toko: <span data-consign-commission class="font-bold text-emerald-400">-</span></p>
                <p class="text-slate-400">Setor ke pemilik: <span data-consign-payout class="font-bold text-white">-</span></p>
                <p class="sm:col-span-2 text-[11px] text-slate-500">
                    Dihitung dari harga jual di atas. Harga modal akun diisi otomatis sebesar bagian pemilik,
                    sehingga laba di laporan = komisi Anda. Data pemilik tidak pernah tampil di halaman publik.
                </p>
            </div>
        </div>
    </div>

    @once
    @push('scripts')
    <script>
        (function () {
            const box = document.querySelector('[data-consign-box]');
            if (!box) return;

            const form    = box.closest('form');
            const toggle  = box.querySelector('[data-consign-toggle]');
            const fields  = box.querySelector('[data-consign-fields]');
            const value   = box.querySelector('input[name="commission_value"]');
            const types   = box.querySelectorAll('input[name="commission_type"]');
            const unit    = box.querySelector('[data-consign-unit]');
            const outCom  = box.querySelector('[data-consign-commission]');
            const outPay  = box.querySelector('[data-consign-payout]');
            const price   = form.querySelector('input[name="price"]');
            const cost    = form.querySelector('input[name="cost_price"]');
            const name    = box.querySelector('input[name="consignor_name"]');
            const rp      = n => 'Rp ' + Math.max(0, Math.round(n)).toLocaleString('id-ID');

            // Modal manual dipulihkan kalau mode titip jual dimatikan lagi.
            let manualCost = toggle.checked ? null : (cost ? cost.value : null);

            const currentType = () => {
                const checked = box.querySelector('input[name="commission_type"]:checked');
                return checked ? checked.value : 'percent';
            };

            function split() {
                const p = parseFloat(price.value) || 0;
                const v = parseFloat(value.value) || 0;
                const c = currentType() === 'fixed' ? Math.min(v, p) : Math.round(p * v / 100);
                return { commission: c, payout: Math.max(0, p - c) };
            }

            function render() {
                const on = toggle.checked;
                fields.classList.toggle('hidden', !on);
                name.required = on;
                value.required = on;
                unit.textContent = currentType() === 'fixed' ? '(Rp)' : '(%)';
                value.max = currentType() === 'fixed' ? '' : '{{ \App\Models\CocAccount::COMMISSION_MAX_PERCENT }}';

                if (cost) {
                    cost.readOnly = on;
                    cost.classList.toggle('opacity-60', on);
                    cost.classList.toggle('cursor-not-allowed', on);
                }

                if (on) {
                    const r = split();
                    outCom.textContent = rp(r.commission);
                    outPay.textContent = rp(r.payout);

                    if (cost) {
                        cost.value = Math.round(r.payout);
                        cost.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                } else if (cost && manualCost !== null) {
                    cost.value = manualCost;
                    cost.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }

            toggle.addEventListener('change', render);
            value.addEventListener('input', render);
            types.forEach(t => t.addEventListener('change', render));
            price.addEventListener('input', render);
            price.addEventListener('change', render);
            render();
        })();
    </script>
    @endpush
    @endonce
@endif