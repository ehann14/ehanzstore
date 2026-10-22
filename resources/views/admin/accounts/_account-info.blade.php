@php
    $val      = fn (string $key, $default = '') => old($key, $account->{$key} ?? $default);
    $inputCls = 'w-full bg-brand-950/50 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:border-brand-500 outline-none transition';
    $lblCls   = 'block text-[10px] font-semibold text-slate-500 mb-1.5 uppercase';
@endphp

<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div>
        <label class="{{ $lblCls }}">Level Akun</label>
        <input type="number" name="account_level" value="{{ $val('account_level', 0) }}" min="0" max="500" inputmode="numeric" placeholder="Contoh: 180"
               class="{{ $inputCls }} @error('account_level') border-red-500 @enderror">
        @error('account_level') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="{{ $lblCls }}">Tukang (Builder)</label>
        <input type="number" name="builder_count" value="{{ $val('builder_count', 5) }}" min="0" max="6" inputmode="numeric" placeholder="Contoh: 6"
               class="{{ $inputCls }} @error('builder_count') border-red-500 @enderror">
        @error('builder_count') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="{{ $lblCls }}">Gems</label>
        <input type="number" name="gems" value="{{ $val('gems', 0) }}" min="0" max="99999999" inputmode="numeric" placeholder="Contoh: 1500"
               class="{{ $inputCls }} @error('gems') border-red-500 @enderror">
        @error('gems') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="{{ $lblCls }}">CN (Change Name)</label>
        <input type="text" name="cn_information" value="{{ $val('cn_information') }}" maxlength="255" placeholder="Contoh: Ready / Sudah dipakai"
               class="{{ $inputCls }} @error('cn_information') border-red-500 @enderror">
        @error('cn_information') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>