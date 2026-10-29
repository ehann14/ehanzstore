{{--
    Pemilih jam 24 jam bergaya gelap (pengganti <select> bawaan browser yang menampilkan daftar putih panjang).

    Pakai:
      <x-time-picker hour-name="until_hour" minute-name="until_minute" :hour="$h" :minute="$m"
                     :step="5" :presets="['19:00','20:00']" label="Pilih jam" />

    Mengirim dua input tersembunyi (jam 0-23 dan menit 0-59), jadi controller tidak perlu diubah.
    Kosong = kedua input berisi string kosong.
    JavaScript: elemen akar punya properti .timepicker { set(h, m), clear(), get() } dan
    memancarkan event "timepicker:change" (detail: { hour, minute }).
--}}
@props([
    'hourName'   => 'hour',
    'minuteName' => 'minute',
    'hour'       => null,
    'minute'     => null,
    'step'       => 5,
    'presets'    => [],
    'label'      => 'Pilih jam',
])
@php
    $hour   = ($hour === null || $hour === '') ? null : max(0, min(23, (int) $hour));
    $minute = ($minute === null || $minute === '') ? null : max(0, min(59, (int) $minute));
    $step   = max(1, min(30, (int) $step));

    $minutes = range(0, 59, $step);
    // Menit tersimpan yang bukan kelipatan langkah tetap ditampilkan supaya tidak hilang diam-diam
    if ($minute !== null && ! in_array($minute, $minutes, true)) {
        $minutes[] = $minute;
        sort($minutes);
    }

    $presets = array_values(array_filter((array) $presets, fn ($p) => is_string($p) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $p)));
@endphp

<div data-timepicker class="relative inline-block min-w-[9.5rem] {{ $attributes->get('class') }}">
    <input type="hidden" name="{{ $hourName }}" value="{{ $hour }}" data-tp-hour>
    <input type="hidden" name="{{ $minuteName }}" value="{{ $minute }}" data-tp-minute>

    <button type="button" data-tp-toggle aria-haspopup="dialog" aria-expanded="false" aria-label="{{ $label }}"
            class="flex w-full items-center gap-2.5 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white transition hover:border-white/20 focus:outline-none focus:border-electric">
        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span data-tp-text class="flex-1 text-left font-mono font-semibold tabular-nums tracking-wide">--:--</span>
        <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div data-tp-panel hidden role="dialog" aria-label="{{ $label }}"
         class="absolute left-0 z-50 w-[19.5rem] max-w-[calc(100vw-3rem)] space-y-3 rounded-2xl border border-white/10 bg-slate-900 p-3 shadow-2xl shadow-black/60">

        @if(count($presets))
            <div>
                <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Jam ramai</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($presets as $p)
                        <button type="button" data-tp-preset="{{ $p }}" class="tp-chip">{{ $p }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Jam <span class="font-medium normal-case tracking-normal text-slate-600">(00-23)</span></p>
            <div class="grid grid-cols-6 gap-1">
                @for($h = 0; $h < 24; $h++)
                    <button type="button" data-tp-h="{{ $h }}" aria-pressed="false" class="tp-cell">{{ sprintf('%02d', $h) }}</button>
                @endfor
            </div>
        </div>

        <div>
            <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Menit</p>
            <div class="grid grid-cols-6 gap-1">
                @foreach($minutes as $m)
                    <button type="button" data-tp-m="{{ $m }}" aria-pressed="false" class="tp-cell">{{ sprintf('%02d', $m) }}</button>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-white/5 pt-2.5">
            <button type="button" data-tp-clear class="rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-400 transition hover:bg-white/5 hover:text-white">Kosongkan</button>
            <button type="button" data-tp-done class="rounded-full bg-electric px-5 py-1.5 text-xs font-bold text-white transition hover:bg-blue-500">Selesai</button>
        </div>
    </div>
</div>

@once
<style>
    .tp-cell {
        height: 2.25rem; border-radius: .65rem; border: 1px solid transparent; background: transparent;
        color: #cbd5e1; cursor: pointer; transition: background .12s, color .12s;
        font: 600 .8125rem ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }
    .tp-cell:hover { background: rgba(255, 255, 255, .08); color: #fff; }
    .tp-cell:focus-visible, .tp-chip:focus-visible { outline: 2px solid #3b82f6; outline-offset: 1px; }
    .tp-cell[aria-pressed="true"] { background: #3b82f6; color: #fff; box-shadow: 0 6px 16px -6px rgba(59, 130, 246, .8); }
    .tp-chip {
        padding: .3rem .7rem; border-radius: 999px; border: 1px solid rgba(255, 255, 255, .12); background: rgba(255, 255, 255, .04);
        color: #e2e8f0; cursor: pointer; transition: background .12s, border-color .12s;
        font: 600 .75rem ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }
    .tp-chip:hover { background: rgba(59, 130, 246, .2); border-color: rgba(59, 130, 246, .5); color: #fff; }
</style>
@push('scripts')
<script>
    (function () {
        var pad = function (n) { return String(n).padStart(2, '0'); };

        function init(root) {
            var hIn    = root.querySelector('[data-tp-hour]');
            var mIn    = root.querySelector('[data-tp-minute]');
            var toggle = root.querySelector('[data-tp-toggle]');
            var text   = root.querySelector('[data-tp-text]');
            var panel  = root.querySelector('[data-tp-panel]');
            var hBtns  = Array.prototype.slice.call(root.querySelectorAll('[data-tp-h]'));
            var mBtns  = Array.prototype.slice.call(root.querySelectorAll('[data-tp-m]'));

            function val(el) { return el.value === '' ? null : parseInt(el.value, 10); }

            function render() {
                var h = val(hIn), m = val(mIn);
                text.textContent = h === null ? '--:--' : pad(h) + ':' + pad(m === null ? 0 : m);
                text.style.color = h === null ? '#64748b' : '';
                hBtns.forEach(function (b) { b.setAttribute('aria-pressed', String(h !== null && parseInt(b.dataset.tpH, 10) === h)); });
                mBtns.forEach(function (b) { b.setAttribute('aria-pressed', String(m !== null && parseInt(b.dataset.tpM, 10) === m)); });
            }

            function emit() {
                var h = val(hIn), m = val(mIn);
                root.dispatchEvent(new CustomEvent('timepicker:change', {
                    bubbles: true,
                    detail: { hour: h, minute: h === null ? null : (m === null ? 0 : m) }
                }));
            }

            function set(h, m) {
                hIn.value = h === null ? '' : String(h);
                mIn.value = h === null ? '' : String(m === null || m === undefined ? 0 : m);
                render();
                emit();
            }

            function close() {
                panel.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
            }

            function open() {
                // tutup picker lain yang sedang terbuka
                document.querySelectorAll('[data-timepicker]').forEach(function (other) {
                    if (other !== root && other.timepicker) { other.timepicker.close(); }
                });

                panel.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');

                // Buka ke atas bila ruang di bawah tidak cukup (mis. tombol di dasar halaman)
                var r = root.getBoundingClientRect();
                var need = panel.offsetHeight + 16;
                var below = window.innerHeight - r.bottom;
                if (below < need && r.top > below) {
                    panel.style.top = 'auto';
                    panel.style.bottom = 'calc(100% + .5rem)';
                } else {
                    panel.style.bottom = 'auto';
                    panel.style.top = 'calc(100% + .5rem)';
                }

                // Geser ke kanan bila melewati tepi layar
                panel.style.left = '0';
                panel.style.right = 'auto';
                if (panel.getBoundingClientRect().right > window.innerWidth - 8) {
                    panel.style.left = 'auto';
                    panel.style.right = '0';
                }
            }

            toggle.addEventListener('click', function () { panel.hidden ? open() : close(); });

            hBtns.forEach(function (b) {
                b.addEventListener('click', function () { set(parseInt(b.dataset.tpH, 10), val(mIn)); });
            });

            mBtns.forEach(function (b) {
                b.addEventListener('click', function () {
                    var m = parseInt(b.dataset.tpM, 10);
                    if (val(hIn) === null) {
                        // jam belum dipilih: simpan menitnya dulu, jam dipilih setelah ini
                        mIn.value = String(m);
                        render();
                        return;
                    }
                    set(val(hIn), m);
                    close();
                });
            });

            root.querySelectorAll('[data-tp-preset]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var p = b.dataset.tpPreset.split(':');
                    set(parseInt(p[0], 10), parseInt(p[1], 10));
                    close();
                });
            });

            root.querySelector('[data-tp-clear]').addEventListener('click', function () { set(null, null); close(); });
            root.querySelector('[data-tp-done]').addEventListener('click', close);

            root.timepicker = {
                set: set,
                clear: function () { set(null, null); },
                get: function () { return { hour: val(hIn), minute: val(mIn) }; },
                close: close
            };

            render();
        }

        document.querySelectorAll('[data-timepicker]').forEach(init);

        // Klik di luar menutup panel; Esc menutup dan mengembalikan fokus ke tombol
        document.addEventListener('click', function (e) {
            document.querySelectorAll('[data-timepicker]').forEach(function (root) {
                if (root.timepicker && !root.contains(e.target)) { root.timepicker.close(); }
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') { return; }
            document.querySelectorAll('[data-timepicker]').forEach(function (root) {
                var panel = root.querySelector('[data-tp-panel]');
                if (panel && !panel.hidden) {
                    root.timepicker.close();
                    root.querySelector('[data-tp-toggle]').focus();
                }
            });
        });
    })();
</script>
@endpush
@endonce