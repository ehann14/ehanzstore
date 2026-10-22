@php
    // $kind: 'account' | 'clan'
    $isClanTag = ($kind ?? 'account') === 'clan';
    $field     = $isClanTag ? 'clan_tag' : 'player_tag';
    $label     = $isClanTag ? 'Tag Clan' : 'Tag Akun';
    $current   = old($field, $account->{$field} ?? '');
@endphp

<label class="block text-xs font-semibold text-slate-400 mb-2">{{ $label }}</label>
<input type="text" name="{{ $field }}" value="{{ $current }}" maxlength="20" autocapitalize="characters" autocomplete="off" spellcheck="false"
       placeholder="{{ $isClanTag ? 'Contoh: #2Y8LQ0R' : 'Contoh: #P2LQ8Y0R' }}"
       class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white font-mono uppercase focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition @error($field) border-red-500 @enderror">
<p class="text-[11px] text-slate-500 mt-1">Boleh dengan atau tanpa tanda #. Otomatis dirapikan saat disimpan.</p>
@error($field) <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror