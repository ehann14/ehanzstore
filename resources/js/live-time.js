/*!
 * EHANZSTORE - waktu realtime
 *  1. [data-countdown]   hitung mundur garansi (berjalan tiap detik)
 *  2. [data-live-clock]  jam / tanggal WIB (time | date | datetime)
 * Teks awal tetap dirender server, jadi halaman tetap benar kalau JS mati.
 */
(function () {
    'use strict';

    var TZ = 'Asia/Jakarta';
    var pad = function (n) { return n < 10 ? '0' + n : '' + n; };

    var TONES = {
        green: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
        amber: 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        red:   'bg-red-500/15 text-red-300 border-red-500/30'
    };
    var ALL_TONE_CLASSES = (TONES.green + ' ' + TONES.amber + ' ' + TONES.red).split(' ');

    var fmtTime, fmtDate;
    try {
        fmtTime = new Intl.DateTimeFormat('en-GB', { timeZone: TZ, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        fmtDate = new Intl.DateTimeFormat('id-ID', { timeZone: TZ, weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
    } catch (e) { /* browser lama: pakai jam perangkat */ }

    function wibTime(d) {
        if (!fmtTime) { return pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()); }
        var p = {};
        fmtTime.formatToParts(d).forEach(function (x) { p[x.type] = x.value; });
        return (p.hour === '24' ? '00' : p.hour) + ':' + p.minute + ':' + p.second;
    }

    function wibDate(d) { return fmtDate ? fmtDate.format(d) : d.toDateString(); }

    function setTone(el, tone) {
        if (el.dataset.tone === tone) { return; }
        ALL_TONE_CLASSES.forEach(function (c) { el.classList.remove(c); });
        TONES[tone].split(' ').forEach(function (c) { el.classList.add(c); });
        el.dataset.tone = tone;
    }

    function tickCountdown(now) {
        var nodes = document.querySelectorAll('[data-countdown]');
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            var end = Date.parse(el.getAttribute('data-countdown'));
            if (isNaN(end)) { continue; }

            var left = Math.floor((end - now) / 1000);
            var text, tone;

            if (left <= 0) {
                text = el.getAttribute('data-expired') || 'Garansi berakhir';
                tone = 'red';
            } else {
                var d = Math.floor(left / 86400);
                var h = Math.floor((left % 86400) / 3600);
                var m = Math.floor((left % 3600) / 60);
                var s = left % 60;
                var clock = pad(h) + ':' + pad(m) + ':' + pad(s);
                text = (el.getAttribute('data-prefix') || '') + (d > 0 ? d + ' hari ' + clock : clock);
                tone = d >= 1 ? 'green' : 'amber';
            }

            if (el.textContent !== text) { el.textContent = text; }
            if (el.hasAttribute('data-badge')) { setTone(el, tone); }
        }
    }

    function tickClock(now) {
        var nodes = document.querySelectorAll('[data-live-clock]');
        if (!nodes.length) { return; }
        var d = new Date(now);
        for (var i = 0; i < nodes.length; i++) {
            var mode = nodes[i].getAttribute('data-live-clock');
            var text = mode === 'date' ? wibDate(d) : (mode === 'datetime' ? wibDate(d) + ', ' + wibTime(d) : wibTime(d));
            if (nodes[i].textContent !== text) { nodes[i].textContent = text; }
        }
    }

    function tick() {
        var now = Date.now();
        tickCountdown(now);
        tickClock(now);
    }

    // Dipakai halaman lain (mis. dashboard) untuk menampilkan jam WIB
    window.EhzTime = { time: function () { return wibTime(new Date()); }, date: function () { return wibDate(new Date()); } };

    function start() {
        tick();
        // Selaraskan dengan pergantian detik supaya angka tidak "loncat"
        setTimeout(function () { tick(); setInterval(tick, 1000); }, 1000 - (Date.now() % 1000));
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();