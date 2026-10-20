@php
    $leagues = \App\Models\CocAccount::CLAN_LEAGUES;
    $val     = fn (string $key, $default = '') => old($key, $account->{$key} ?? $default);
    $inputCls = 'w-full bg-brand-950/50 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:border-brand-500 outline-none transition';
    $lblCls   = 'block text-[10px] font-semibold text-slate-500 mb-1.5 uppercase';
@endphp

<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div>
        <label class="{{ $lblCls }}">Level Clan <span class="text-red-400">*</span></label>
        <input type="number" name="clan_level" value="{{ $val('clan_level') }}" min="1" max="100" required placeholder="Contoh: 20" class="{{ $inputCls }} @error('clan_level') border-red-500 @enderror">
        @error('clan_level') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="{{ $lblCls }}">Capital Hall <span class="text-red-400">*</span></label>
        <input type="number" name="capital_hall_level" value="{{ $val('capital_hall_level') }}" min="0" max="15" required placeholder="Contoh: 10" class="{{ $inputCls }} @error('capital_hall_level') border-red-500 @enderror">
        @error('capital_hall_level') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="{{ $lblCls }}">Jumlah Member <span class="text-red-400">*</span></label>
        <input type="number" name="clan_members" value="{{ $val('clan_members') }}" min="0" max="{{ \App\Models\CocAccount::CLAN_MAX_MEMBERS }}" required placeholder="Maks. {{ \App\Models\CocAccount::CLAN_MAX_MEMBERS }}" class="{{ $inputCls }} @error('clan_members') border-red-500 @enderror">
        @error('clan_members') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="col-span-2 md:col-span-1">
        <label class="{{ $lblCls }}">Liga Clan <span class="text-red-400">*</span></label>
        <select name="clan_league" required class="{{ $inputCls }} @error('clan_league') border-red-500 @enderror">
            <option value="">Pilih liga</option>
            @foreach($leagues as $league)
                <option value="{{ $league }}" class="bg-slate-900" {{ $val('clan_league') === $league ? 'selected' : '' }}>{{ $league }}</option>
            @endforeach
        </select>
        @error('clan_league') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>