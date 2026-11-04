{{--
    Lonceng notifikasi admin: ulasan baru & booking baru.
    - Data dibaca dari route admin.notifications.feed (cek berkala tiap 20 detik)
    - Notifikasi baru memunculkan toast + bunyi singkat + angka di lonceng
    - Isi notifikasi ditulis dengan textContent (aman dari HTML/script di teks ulasan pembeli)
    Dipasang sekali di layouts/admin.blade.php; tombol lonceng memakai class .ntf-bell
--}}
<!-- Panel notifikasi -->
<div id="ntfPanel" class="hidden fixed z-[70] w-[calc(100vw-1.5rem)] max-w-sm glass-strong rounded-2xl border border-white/10 shadow-2xl overflow-hidden"
     role="dialog" aria-label="Notifikasi">
    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-white/5">
        <p class="font-display font-bold text-white text-sm">Notifikasi</p>
        <button type="button" id="ntfReadAll" class="text-xs text-electric hover:text-white transition">Tandai semua dibaca</button>
    </div>
    <div id="ntfList" class="max-h-[60vh] overflow-y-auto custom-scrollbar divide-y divide-white/5">
        <p class="px-4 py-8 text-center text-sm text-slate-500">Memuat…</p>
    </div>
</div>

<!-- Toast notifikasi baru -->
<div id="ntfToasts" class="fixed z-[80] right-3 left-3 sm:left-auto sm:w-96 space-y-2 pointer-events-none"
     style="top: calc(env(safe-area-inset-top, 0px) + .75rem)"></div>

<script>
(function () {
    'use strict';

    var FEED_URL = '{{ route('admin.notifications.feed', [], false) }}';
    var READ_URL = '{{ url('/admin/notifications') }}';
    var POLL_MS  = 20000;

    var panel   = document.getElementById('ntfPanel');
    var list    = document.getElementById('ntfList');
    var toasts  = document.getElementById('ntfToasts');
    var readAll = document.getElementById('ntfReadAll');
    var knownIds = null;          // null = belum pernah memuat (jangan munculkan toast untuk data lama)
    var baseTitle = document.title;
    var audioCtx = null;

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function post(url) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).catch(function () {});
    }

    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) { n.className = cls; }
        if (text != null) { n.textContent = text; }
        return n;
    }

    // Ikon per jenis: bintang (ulasan) / kalender (booking)
    function icon(kind) {
        var wrap = el('span', 'shrink-0 w-9 h-9 rounded-xl flex items-center justify-center '
            + (kind === 'review' ? 'bg-amber-500/15 text-amber-300' : 'bg-electric/15 text-blue-300'));
        var d = kind === 'review'
            ? 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'
            : 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z';
        wrap.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + d + '"/></svg>';
        return wrap;
    }

    function open(item) {
        var go = function () { if (item.url) { window.location.href = item.url; } };
        if (!item.read) {
            post(READ_URL + '/' + encodeURIComponent(item.id) + '/read').then(go);
        } else {
            go();
        }
    }

    function render(items) {
        list.textContent = '';

        if (!items.length) {
            list.appendChild(el('p', 'px-4 py-8 text-center text-sm text-slate-500', 'Belum ada notifikasi.'));
            return;
        }

        items.forEach(function (item) {
            var row = el('button', 'w-full text-left flex gap-3 px-4 py-3 hover:bg-white/5 transition '
                + (item.read ? 'opacity-60' : 'bg-electric/5'));
            row.type = 'button';
            row.appendChild(icon(item.kind));

            var body = el('div', 'min-w-0 flex-1');
            var head = el('div', 'flex items-center gap-2');
            head.appendChild(el('p', 'text-sm font-semibold text-white truncate', item.title));
            if (!item.read) { head.appendChild(el('span', 'shrink-0 w-2 h-2 rounded-full bg-electric')); }
            body.appendChild(head);
            body.appendChild(el('p', 'text-xs text-slate-400 mt-0.5 break-words', item.message));
            body.appendChild(el('p', 'text-[10px] text-slate-600 mt-1', item.time));
            row.appendChild(body);

            row.addEventListener('click', function () { open(item); });
            list.appendChild(row);
        });
    }

    function setBadge(n) {
        document.querySelectorAll('.ntf-badge').forEach(function (b) {
            b.textContent = n > 99 ? '99+' : n;
            b.style.display = n < 1 ? 'none' : 'flex';
        });
        document.title = n > 0 ? '(' + n + ') ' + baseTitle : baseTitle;
    }

    function beep() {
        try {
            var AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) { return; }
            audioCtx = audioCtx || new AC();
            var o = audioCtx.createOscillator(), g = audioCtx.createGain();
            o.type = 'sine'; o.frequency.value = 880;
            g.gain.setValueAtTime(0.0001, audioCtx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.15, audioCtx.currentTime + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.35);
            o.connect(g); g.connect(audioCtx.destination);
            o.start(); o.stop(audioCtx.currentTime + 0.36);
        } catch (e) { /* browser memblokir audio sebelum ada interaksi: abaikan */ }
    }

    function toast(item) {
        var t = el('button', 'pointer-events-auto w-full text-left flex gap-3 p-3 rounded-2xl glass-strong border border-electric/30 shadow-2xl transition');
        t.type = 'button';
        t.appendChild(icon(item.kind));
        var body = el('div', 'min-w-0 flex-1');
        body.appendChild(el('p', 'text-sm font-semibold text-white', item.title));
        body.appendChild(el('p', 'text-xs text-slate-300 mt-0.5 break-words', item.message));
        t.appendChild(body);
        t.addEventListener('click', function () { open(item); });
        toasts.appendChild(t);
        setTimeout(function () { t.remove(); }, 8000);
    }

    function load() {
        fetch(FEED_URL, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) { return; }
                var items = d.items || [];

                if (knownIds !== null) {
                    var fresh = items.filter(function (i) { return !i.read && knownIds.indexOf(i.id) === -1; });
                    if (fresh.length) {
                        beep();
                        fresh.slice(0, 3).forEach(toast);
                    }
                }
                knownIds = items.map(function (i) { return i.id; });

                setBadge(d.unread || 0);
                render(items);
            })
            .catch(function () { /* offline: coba lagi pada siklus berikutnya */ });
    }

    // ----- Buka / tutup panel -----
    function placePanel(btn) {
        var wide = window.innerWidth >= 1024;
        var r = btn.getBoundingClientRect();
        panel.style.left = panel.style.right = panel.style.top = '';
        if (wide) {
            // Desktop: muncul di sebelah kanan sidebar, sejajar tombol lonceng
            panel.style.left = (r.right + 12) + 'px';
            panel.style.top  = Math.max(12, r.top) + 'px';
        } else {
            panel.style.left = '.75rem';
            panel.style.top  = (r.bottom + 8) + 'px';
        }
    }

    function togglePanel(btn) {
        if (panel.classList.contains('hidden')) {
            placePanel(btn);
            panel.classList.remove('hidden');
            load();
        } else {
            panel.classList.add('hidden');
        }
    }

    document.querySelectorAll('.ntf-bell').forEach(function (btn) {
        btn.addEventListener('click', function (e) { e.stopPropagation(); togglePanel(btn); });
    });
    panel.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () { panel.classList.add('hidden'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { panel.classList.add('hidden'); } });
    window.addEventListener('resize', function () { panel.classList.add('hidden'); });

    readAll.addEventListener('click', function () {
        post(READ_URL + '/read-all').then(load);
    });

    load();
    setInterval(function () { if (document.visibilityState === 'visible') { load(); } }, POLL_MS);
    document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible') { load(); } });
})();
</script>