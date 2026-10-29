{{--
    Jadwal tayang akun: siapkan akun malam hari, terbit otomatis di jam ramai.
    Variabel: $account (CocAccount|null)
    Hanya berlaku untuk status Draft. Bila status diganti Terbit, jadwal diabaikan dan dihapus.
    Waktu dibaca dalam WIB; controller menyimpannya ke kolom publish_at.
--}}
@php
    $pub   = $account && $account->isScheduled()
        ? $account->publish_at->clone()->timezone(\App\Models\Sale::DISPLAY_TZ)
        : null;
    $pDate = old('publish_date', $pub?->format('Y-m-d') ?? '');
    $pHour = old('publish_hour', $pub?->format('H') ?? '');
    $pMin  = old('publish_minute', $pub?->format('i') ?? '');
    $peak  = (array) config('store.peak_hours', ['12:00', '19:00', '20:00', '21:00']);
@endphp

<div class="space-y-4">
    <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider border-b border-white/5 pb-2">Jadwal Tayang</h3>

    <div data-publish-schedule class="rounded-2xl border border-white/10 bg-white/[0.02] p-4 space-y-3 transition-opacity">
        <p class="text-[11px] text-slate-500 leading-relaxed">
            Siapkan akun kapan saja, lalu atur kapan ia tampil di katalog. Akun tetap <b class="text-slate-400">Draft</b> (tersembunyi) sampai waktunya tiba, lalu terbit otomatis.
            Kosongkan tanggal bila tidak ingin dijadwalkan.
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <input type="date" name="publish_date" value="{{ $pDate }}" aria-label="Tanggal tayang"
                   class="min-w-[10rem] flex-1 bg-brand-950/50 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white focus:border-brand-500 outline-none transition [color-scheme:dark] @error('publish_date') border-red-500 @enderror">
            <x-time-picker hour-name="publish_hour" minute-name="publish_minute" :hour="$pHour" :minute="$pMin"
                           :step="5" :presets="$peak" label="Pilih jam tayang" />
            <span class="text-xs font-semibold text-slate-400">WIB</span>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach([0 => 'Hari ini', 1 => 'Besok', 2 => 'Lusa'] as $add => $label)
                <button type="button" data-ps-day="{{ $add }}"
                        class="px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-[11px] font-semibold text-slate-300 transition">{{ $label }}</button>
            @endforeach
            <button type="button" data-ps-clear
                    class="px-3 py-1.5 rounded-lg border border-white/10 text-[11px] font-semibold text-slate-500 hover:text-white hover:bg-white/5 transition">Kosongkan jadwal</button>
        </div>

        @error('publish_date') <p class="text-red-400 text-xs">{{ $message }}</p> @enderror
        @error('publish_hour') <p class="text-red-400 text-xs">{{ $message }}</p> @enderror
        @error('publish_minute') <p class="text-red-400 text-xs">{{ $message }}</p> @enderror

        <p data-ps-summary class="text-xs text-slate-400 min-h-[1rem]"></p>
    </div>
</div>

@once
@push('scripts')
<script>
    (function () {
        var box = document.querySelector('[data-publish-schedule]');
        if (!box) { return; }

        var form    = box.closest('form');
        var dateIn  = box.querySelector('input[name="publish_date"]');
        var picker  = box.querySelector('[data-timepicker]');
        var summary = box.querySelector('[data-ps-summary]');
        var status  = form ? form.querySelector('select[name="publication_status"]') : null;

        // Waktu sekarang dalam WIB, tidak bergantung zona waktu perangkat
        var fmt = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', hour12: false
        });
        function parts(ms) {
            var p = {};
            fmt.formatToParts(new Date(ms)).forEach(function (x) { p[x.type] = x.value; });
            return { date: p.year + '-' + p.month + '-' + p.day, hm: (p.hour === '24' ? '00' : p.hour) + ':' + p.minute };
        }
        function pad(n) { return String(n).padStart(2, '0'); }

        function refresh() {
            var t = picker.timepicker.get();
            summary.className = 'text-xs text-slate-400 min-h-[1rem]';

            if (!dateIn.value && t.hour === null) { summary.textContent = ''; return; }
            if (!dateIn.value || t.hour === null) {
                summary.textContent = !dateIn.value ? 'Pilih tanggal tayang.' : 'Pilih jam tayang.';
                return;
            }

            var hm  = pad(t.hour) + ':' + pad(t.minute === null ? 0 : t.minute);
            var now = parts(Date.now());

            if (dateIn.value + 'T' + hm <= now.date + 'T' + now.hm) {
                summary.className = 'text-xs text-amber-400 min-h-[1rem]';
                summary.textContent = 'Waktu ini sudah lewat. Pilih waktu di masa depan.';
                return;
            }

            var d = dateIn.value.split('-');
            var label = new Date(+d[0], +d[1] - 1, +d[2]).toLocaleDateString('id-ID', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
            summary.className = 'text-xs text-emerald-400 min-h-[1rem]';
            summary.textContent = 'Terbit otomatis ' + label + ', pukul ' + hm + ' WIB.';
        }

        // Memilih jam tanpa tanggal: isi tanggal otomatis (hari ini bila jamnya belum lewat, kalau sudah lewat besok)
        picker.addEventListener('timepicker:change', function (e) {
            if (e.detail.hour !== null && !dateIn.value) {
                var now = parts(Date.now());
                var hm  = pad(e.detail.hour) + ':' + pad(e.detail.minute);
                dateIn.value = hm > now.hm ? now.date : parts(Date.now() + 86400000).date;
            }
            refresh();
        });
        dateIn.addEventListener('input', refresh);
        dateIn.addEventListener('change', refresh);

        box.querySelectorAll('[data-ps-day]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                dateIn.value = parts(Date.now() + Number(btn.dataset.psDay) * 86400000).date;
                refresh();
            });
        });

        box.querySelector('[data-ps-clear]').addEventListener('click', function () {
            dateIn.value = '';
            picker.timepicker.clear();
            refresh();
        });

        // Status Terbit (halaman edit): jadwal hanya berlaku bila akun masih Draft
        function syncStatus() {
            if (!status) { return; }
            var live = status.value === 'published';
            box.style.opacity = live ? '.45' : '';
            box.inert = live;
            if (live) { picker.timepicker.close(); }
        }
        if (status) { status.addEventListener('change', syncStatus); }

        syncStatus();
        refresh();
    })();
</script>
@endpush
@endonce