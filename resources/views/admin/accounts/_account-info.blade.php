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

{{-- Email (Gmail) yang dipakai akun game, dipilih dari daftar Email Akun. HANYA admin yang melihat, tidak pernah tampil ke pembeli. --}}
@php
    $emailOptions  = $emailOptions ?? collect();
    $selectedEmail = (string) old('account_email_id', $account->account_email_id ?? '');
    $isSold        = $account && $account->sale_status === 'sold';
@endphp
<div class="mt-4">
    <label class="{{ $lblCls }}">
        Email Akun (Gmail)
        <span class="ml-1 normal-case font-semibold text-amber-300">&#128274; Hanya admin, tidak tampil ke pembeli</span>
    </label>

    @if($isSold)
        <div class="rounded-lg border border-white/10 bg-brand-950/30 px-3 py-2.5 text-sm text-slate-400">
            Akun sudah terjual, jadi email otomatis dilepas dan bisa dipakai akun lain.
        </div>
    @else
        <div class="flex gap-2">
            <select name="account_email_id" id="accountEmailSelect"
                    class="{{ $inputCls }} @error('account_email_id') border-red-500 @enderror">
                <option value="">- Belum dipilih -</option>
                @foreach($emailOptions as $opt)
                    <option value="{{ $opt->id }}" {{ $selectedEmail === (string) $opt->id ? 'selected' : '' }}>
                        {{ $opt->email }}{{ $opt->note ? ' (' . $opt->note . ')' : '' }}
                    </option>
                @endforeach
            </select>
            <button type="button"
                    onclick="(function(){var s=document.getElementById('accountEmailSelect');var o=s&&s.options[s.selectedIndex];if(!o||!o.value)return;var v=o.text.split(' (')[0].trim();navigator.clipboard&&navigator.clipboard.writeText(v)})()"
                    class="shrink-0 px-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-slate-300 transition">
                Salin
            </button>
        </div>
        <p class="text-[11px] text-slate-500 mt-1">
            Satu email hanya untuk satu akun. Email akun yang sudah terjual otomatis dilepas dan muncul lagi di sini.
            @if($emailOptions->isEmpty())
                <span class="text-amber-300">Belum ada email tersedia.</span>
            @endif
            <a href="{{ route('admin.account-emails.index') }}" target="_blank" class="text-electric hover:underline">Kelola daftar email</a>
        </p>
    @endif
    @error('account_email_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
</div>