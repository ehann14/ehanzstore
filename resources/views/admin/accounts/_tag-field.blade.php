@php
    // $kind: 'account' | 'clan'
    $isClanTag = ($kind ?? 'account') === 'clan';
    $field     = $isClanTag ? 'clan_tag' : 'player_tag';
    $label     = $isClanTag ? 'Tag Clan' : 'Tag Akun';
    $current   = old($field, $account->{$field} ?? '');
@endphp

<label class="block text-xs font-semibold text-slate-400 mb-2">{{ $label }}</label>
<div class="flex gap-2">
    <input type="text" name="{{ $field }}" value="{{ $current }}" maxlength="20" autocapitalize="characters" autocomplete="off" spellcheck="false"
           placeholder="{{ $isClanTag ? 'Contoh: #2Y8LQ0R' : 'Contoh: #P2LQ8Y0R' }}"
           class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white font-mono uppercase focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition @error($field) border-red-500 @enderror">
    @unless($isClanTag)
        {{-- Isi otomatis TH, level akun & level hero dari API Clash of Clans. Tukang, gems dan CN tetap manual. --}}
        <button type="button" id="coc-lookup-btn" data-url="{{ route('admin.accounts.lookup-player') }}"
                class="shrink-0 px-4 rounded-xl border border-emerald-500/30 text-emerald-300 hover:bg-emerald-500/10 text-xs font-semibold transition disabled:opacity-50">
            Isi Otomatis
        </button>
    @endunless
</div>
<p class="text-[11px] text-slate-500 mt-1">Boleh dengan atau tanpa tanda #. Otomatis dirapikan saat disimpan.</p>
@unless($isClanTag)
    <p id="coc-lookup-msg" class="text-[11px] mt-1 hidden"></p>
@endunless
@error($field) <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror

@unless($isClanTag)
    @once
    <script>
        (function () {
            const btn   = document.getElementById('coc-lookup-btn');
            const input = document.querySelector('input[name="player_tag"]');
            const msg   = document.getElementById('coc-lookup-msg');
            if (!btn || !input || !msg) return;

            function say(text, ok) {
                msg.textContent = text;
                msg.className = 'text-[11px] mt-1 ' + (ok ? 'text-emerald-400' : 'text-red-400');
            }

            // Isi field dan beri tahu skrip lain di form (mis. pratinjau) bahwa nilainya berubah
            function setField(name, value) {
                const el = document.querySelector('[name="' + name + '"]');
                if (!el) return;
                el.value = value;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }

            btn.addEventListener('click', async function () {
                const tag = input.value.trim();
                if (!tag) { say('Isi tag akun dulu.', false); return; }

                btn.disabled = true;
                const oldText = btn.textContent;
                btn.textContent = 'Memuat...';
                msg.className = 'hidden';

                try {
                    const res  = await fetch(btn.dataset.url + '?tag=' + encodeURIComponent(tag), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    });
                    const json = await res.json().catch(() => null);

                    if (!res.ok || !json || !json.ok) {
                        say((json && json.message) || 'Gagal mengambil data (kode ' + res.status + ').', false);
                        return;
                    }

                    const d = json.data;
                    input.value = d.tag || input.value;
                    setField('town_hall', d.town_hall);
                    setField('account_level', d.account_level);
                    Object.keys(d.heroes).forEach(function (key) { setField(key, d.heroes[key]); });

                    const heroes = Object.values(d.heroes).filter(function (v) { return v > 0; }).join('/');
                    say('Terisi dari ' + d.name + (d.clan ? ' (' + d.clan + ')' : '') + ': TH ' + d.town_hall
                        + ', level ' + d.account_level + (heroes ? ', hero ' + heroes : '')
                        + '. Tukang, gems dan CN isi manual. Periksa lagi sebelum menyimpan.', true);
                } catch (e) {
                    say('Tidak bisa menghubungi server. Periksa koneksi.', false);
                } finally {
                    btn.disabled = false;
                    btn.textContent = oldText;
                }
            });
        })();
    </script>
    @endonce
@endunless