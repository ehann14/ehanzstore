{{--
    Bar melayang "Bandingkan akun" (2 sampai 3 akun, sesama akun atau sesama clan).
    Pilihan disimpan di localStorage browser (kunci: ehz_compare), jadi tetap ada saat pindah halaman.
    Tombol pemilih ada di kartu akun & halaman detail: atribut [data-compare-btn].
--}}
<div id="compareBar" data-compare-url="{{ route('compare') }}"
     class="hidden fixed z-40 bottom-3 left-3 right-24 sm:right-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-[min(40rem,calc(100vw-11rem))]"
     role="region" aria-label="Bandingkan akun">
    <div class="glass-strong rounded-2xl border border-white/10 shadow-2xl shadow-black/60 p-2.5 sm:p-3">
        <p id="compareNotice" class="hidden text-[11px] text-amber-300 mb-2 px-1" role="status"></p>
        <div class="flex items-center gap-2 sm:gap-2.5">
            <div id="compareChips" class="flex-1 min-w-0 flex gap-2 overflow-x-auto no-scrollbar"></div>
            <button type="button" id="compareClear"
                    class="shrink-0 text-[11px] text-slate-400 hover:text-white px-1.5 py-1.5 transition">Kosongkan</button>
            <a id="compareGo" href="#"
               class="shrink-0 px-3 sm:px-4 py-2 rounded-xl bg-electric hover:bg-blue-500 text-white text-xs sm:text-sm font-bold transition">
                Bandingkan (<span id="compareCount">0</span>)
            </a>
        </div>
    </div>
</div>

<script>
    (function () {
        var KEY = 'ehz_compare';
        var MAX = 3;
        var bar = document.getElementById('compareBar');
        if (!bar) return;

        var chips    = document.getElementById('compareChips');
        var notice   = document.getElementById('compareNotice');
        var go       = document.getElementById('compareGo');
        var countEl  = document.getElementById('compareCount');
        var clearBtn = document.getElementById('compareClear');
        var noticeTimer = null;

        function read() {
            try {
                var data = JSON.parse(localStorage.getItem(KEY) || '[]');
                if (!Array.isArray(data)) return [];
                return data.filter(function (x) {
                    return x && typeof x.code === 'string' && x.code;
                }).slice(0, MAX);
            } catch (e) {
                return [];
            }
        }

        function write(list) {
            try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {}
        }

        var list = read();

        function indexOf(code) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].code === code) return i;
            }
            return -1;
        }

        function say(msg) {
            notice.textContent = msg;
            notice.classList.remove('hidden');
            clearTimeout(noticeTimer);
            noticeTimer = setTimeout(function () { notice.classList.add('hidden'); }, 3500);
        }

        function paintButtons() {
            var buttons = document.querySelectorAll('[data-compare-btn]');
            for (var i = 0; i < buttons.length; i++) {
                var b = buttons[i];
                var on = indexOf(b.getAttribute('data-code')) > -1;
                b.setAttribute('aria-pressed', on ? 'true' : 'false');

                var label = b.querySelector('[data-compare-label]');
                if (label) label.textContent = on ? (label.getAttribute('data-on') || 'Dipilih') : (label.getAttribute('data-off') || 'Bandingkan');

                var iconOn  = b.querySelector('[data-cmp-on]');
                var iconOff = b.querySelector('[data-cmp-off]');
                if (iconOn)  iconOn.classList.toggle('hidden', !on);
                if (iconOff) iconOff.classList.toggle('hidden', on);
            }
        }

        function paintBar() {
            chips.innerHTML = '';

            list.forEach(function (item) {
                var chip = document.createElement('div');
                chip.className = 'shrink-0 flex items-center gap-1.5 max-w-[9.5rem] rounded-lg bg-white/5 border border-white/10 pl-1 pr-1 py-1';

                if (item.img) {
                    var img = document.createElement('img');
                    img.src = item.img;
                    img.alt = '';
                    img.className = 'w-7 h-7 rounded object-cover shrink-0';
                    img.onerror = function () { img.style.display = 'none'; };
                    chip.appendChild(img);
                }

                var name = document.createElement('span');
                name.className = 'text-[11px] text-slate-200 truncate';
                name.textContent = item.name || item.code;
                chip.appendChild(name);

                var rm = document.createElement('button');
                rm.type = 'button';
                rm.setAttribute('data-compare-remove', item.code);
                rm.setAttribute('aria-label', 'Hapus ' + (item.name || item.code) + ' dari perbandingan');
                rm.className = 'shrink-0 w-5 h-5 rounded text-slate-400 hover:text-white hover:bg-white/10 flex items-center justify-center text-sm leading-none';
                rm.textContent = '\u00d7';
                chip.appendChild(rm);

                chips.appendChild(chip);
            });

            countEl.textContent = list.length;

            var ready = list.length >= 2;
            go.classList.toggle('opacity-50', !ready);
            go.setAttribute('aria-disabled', ready ? 'false' : 'true');
            go.href = ready
                ? bar.getAttribute('data-compare-url') + '?akun=' + list.map(function (x) { return encodeURIComponent(x.code); }).join(',')
                : '#';

            bar.classList.toggle('hidden', list.length === 0);
            if (!list.length) notice.classList.add('hidden');
        }

        function render() {
            paintButtons();
            paintBar();
        }

        function toggle(btn) {
            var code = btn.getAttribute('data-code');
            if (!code) return;

            var i = indexOf(code);
            if (i > -1) {
                list.splice(i, 1);
            } else {
                var type = btn.getAttribute('data-type') || 'account';

                if (list.length >= MAX) {
                    say('Maksimal ' + MAX + ' untuk dibandingkan. Hapus salah satu dulu.');
                    render();
                    return;
                }
                if (list.length && list[0].type !== type) {
                    say('Bandingkan sesama akun atau sesama clan saja.');
                    render();
                    return;
                }

                list.push({
                    code:  code,
                    name:  btn.getAttribute('data-name') || code,
                    type:  type,
                    img:   btn.getAttribute('data-img') || '',
                    price: btn.getAttribute('data-price') || ''
                });
            }

            write(list);
            render();
        }

        // Capture phase: tombol berada di dalam kartu yang berupa link, jadi klik harus dicegat
        // sebelum browser membuka halaman detail.
        document.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('[data-compare-btn]') : null;
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            toggle(btn);
        }, true);

        chips.addEventListener('click', function (e) {
            var rm = e.target.closest ? e.target.closest('[data-compare-remove]') : null;
            if (!rm) return;
            var i = indexOf(rm.getAttribute('data-compare-remove'));
            if (i > -1) list.splice(i, 1);
            write(list);
            render();
        });

        clearBtn.addEventListener('click', function () {
            list = [];
            write(list);
            render();
        });

        go.addEventListener('click', function (e) {
            if (list.length < 2) {
                e.preventDefault();
                say('Pilih minimal 2 untuk dibandingkan.');
            }
        });

        // Sinkron saat kembali lewat tombol back (bfcache) atau saat tab lain mengubah pilihan
        window.addEventListener('pageshow', function () { list = read(); render(); });
        window.addEventListener('storage', function (e) {
            if (e.key === KEY) { list = read(); render(); }
        });

        render();
    })();
</script>