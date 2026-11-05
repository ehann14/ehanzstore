{{--
    Laporan konversi untuk dashboard Super Admin (data dari App\Support\ConversionReport, variabel $conversion).
    Dibungkus data-live="conversion" supaya ikut diperbarui otomatis oleh skrip live dashboard.
--}}
@php
    $cv   = $conversion;
    $f    = $cv['funnel'];
    $dp   = $cv['dp'];
    $rp   = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $pct  = fn ($v) => $v === null ? '-' : number_format($v, 1, ',', '.') . '%';
    $retentionDays = (int) config('store.visitor_tracking.retention_days', 90);

    $steps = [
        ['label' => 'Pengunjung unik', 'hint' => 'membuka website',                'count' => $f['visitors'], 'rate' => null,                'rateNote' => '',                  'bar' => 'bg-sky-500',     'text' => 'text-sky-300'],
        ['label' => 'Melihat akun',    'hint' => 'membuka halaman detail akun',    'count' => $f['viewers'],  'rate' => $f['view_rate'],     'rateNote' => 'dari pengunjung',   'bar' => 'bg-electric',    'text' => 'text-blue-300'],
        ['label' => 'Booking (DP)',    'hint' => 'booking dibuat, ' . $f['bookings_paid'] . ' sudah TF', 'count' => $f['bookings'], 'rate' => $f['booking_rate'], 'rateNote' => 'dari pelihat akun', 'bar' => 'bg-amber-400', 'text' => 'text-amber-300'],
        ['label' => 'Terjual',         'hint' => 'transaksi selesai',              'count' => $f['sold'],     'rate' => $f['sold_rate'],     'rateNote' => 'dari pelihat akun', 'bar' => 'bg-emerald-500', 'text' => 'text-emerald-300'],
    ];
    $barBase = max(1, $f['visitors']);
@endphp

<div id="konversi" data-live="conversion" class="scroll-mt-24 mb-6">

    {{-- ===== Funnel ===== --}}
    <div class="glass-panel rounded-2xl p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="font-bold text-white">Laporan Konversi</h3>
                <p class="text-xs text-slate-500 mt-0.5">Dari pengunjung, melihat akun, booking, sampai terjual &middot; {{ $cv['days'] }} hari terakhir</p>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                @foreach(\App\Support\ConversionReport::RANGES as $d)
                    <a href="{{ request()->fullUrlWithQuery(['konversi' => $d]) }}#konversi"
                       class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition {{ $cv['days'] === $d ? 'bg-electric/15 border-electric/40 text-blue-300' : 'border-white/10 text-slate-400 hover:text-white hover:bg-white/5' }}">{{ $d }} hari</a>
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            @foreach($steps as $step)
                @php
                    $w = $step['count'] > 0 ? max(2, min(100, round($step['count'] / $barBase * 100))) : 0;
                @endphp
                <div>
                    <div class="flex items-baseline justify-between gap-3 mb-1.5">
                        <div class="min-w-0">
                            <span class="text-sm font-semibold text-white">{{ $step['label'] }}</span>
                            <span class="text-[11px] text-slate-500 ml-1.5">{{ $step['hint'] }}</span>
                        </div>
                        <div class="shrink-0 text-right whitespace-nowrap">
                            <span class="text-lg font-display font-bold {{ $step['text'] }}">{{ number_format($step['count']) }}</span>
                            @if($step['rate'] !== null)
                                <span class="text-[11px] text-slate-400 ml-1.5"><b class="text-slate-200">{{ $pct($step['rate']) }}</b> {{ $step['rateNote'] }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="h-2.5 rounded-full bg-white/5 overflow-hidden">
                        <div class="h-full rounded-full {{ $step['bar'] }}" style="width: {{ $w }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2 mt-5">
            <span class="px-2.5 py-1 rounded-full border border-white/10 bg-white/[0.03] text-[11px] text-slate-300"><b class="text-white">{{ number_format($f['wa_clicks']) }}</b> pengunjung klik Beli via WhatsApp</span>
            <span class="px-2.5 py-1 rounded-full border border-white/10 bg-white/[0.03] text-[11px] text-slate-300"><b class="text-white">{{ number_format($f['sold_via_booking']) }}</b> terjual lewat booking DP</span>
            <span class="px-2.5 py-1 rounded-full border border-white/10 bg-white/[0.03] text-[11px] text-slate-300"><b class="text-white">{{ number_format($f['sold_direct']) }}</b> terjual langsung (lunas tanpa booking)</span>
        </div>

        <p class="text-[11px] text-slate-500 mt-4 leading-relaxed">
            Booking dan penjualan dicatat per transaksi, tidak terhubung ke sesi pengunjung, jadi persentase di atas adalah perbandingan jumlah, bukan pelacakan per orang.
            @if($retentionDays > 0 && $cv['days'] > $retentionDays)
                Data pengunjung hanya disimpan {{ $retentionDays }} hari, jadi angka pengunjung untuk rentang ini belum lengkap.
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ===== Ramai dilihat tapi belum laku ===== --}}
        <div class="lg:col-span-2 glass-panel rounded-2xl p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3 mb-1">
                <h3 class="font-bold text-white">Banyak Dilihat, Belum Laku</h3>
                @if(count($cv['candidates']) > 0)
                    <span class="shrink-0 px-2.5 py-1 rounded-full border text-[11px] font-semibold bg-amber-500/15 text-amber-300 border-amber-500/30">{{ count($cv['candidates']) }} kandidat</span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mb-4">
                Kandidat turun harga: akun tersedia yang dilihat minimal {{ $cv['min_viewers'] }} pengunjung unik dalam {{ $cv['days'] }} hari, belum terjual dan tidak sedang dibooking.
            </p>

            @forelse($cv['candidates'] as $c)
                @php
                    $a = $c['account'];
                    $s = $c['suggestion'];
                @endphp
                <div class="py-3 border-t border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-white font-semibold truncate">{{ $a->name }}
                            <span class="text-[11px] text-slate-500 font-mono ml-1">{{ $a->account_code }}</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <span class="text-brand-300 font-semibold">{{ $c['viewers'] }} orang</span> melihat ({{ $c['views'] }}x)
                            &middot; {{ $c['wa'] }} klik WA
                            &middot; {{ $c['bookings'] }} booking
                            &middot; terpajang {{ $c['days_listed'] }} hari
                        </p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 sm:justify-end">
                        <div class="sm:text-right">
                            <p class="text-sm font-bold text-white whitespace-nowrap">{{ $rp($a->price) }}</p>
                            @if($s)
                                <p class="text-[11px] text-amber-300 whitespace-nowrap">Saran {{ $rp($s['price']) }} (-{{ rtrim(rtrim(number_format($s['percent'], 1, ',', '.'), '0'), ',') }}%)@if($s['at_cost']) &middot; batas modal @endif</p>
                            @else
                                <p class="text-[11px] text-slate-500 whitespace-nowrap">Tidak ada ruang turun harga</p>
                            @endif
                        </div>
                        <a href="{{ route('admin.accounts.edit', $a) }}"
                           class="inline-flex items-center px-3.5 py-2 rounded-xl border border-amber-500/30 text-amber-300 hover:bg-amber-500/10 text-xs font-semibold transition whitespace-nowrap">Edit harga</a>
                    </div>
                </div>
            @empty
                <div class="text-center py-10 text-slate-500 text-sm border-t border-white/5">
                    Belum ada akun yang ramai dilihat tanpa terjual pada periode ini.
                </div>
            @endforelse
        </div>

        {{-- ===== DP hangus per bulan ===== --}}
        <div class="glass-panel rounded-2xl p-5 sm:p-6">
            <h3 class="font-bold text-white mb-1">DP Hangus per Bulan</h3>
            <p class="text-xs text-slate-500 mb-4">DP yang tidak dikembalikan karena pembeli batal atau tidak melunasi tepat waktu.</p>

            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="rounded-xl bg-red-500/10 border border-red-500/20 p-3">
                    <p class="text-[10px] uppercase tracking-wider text-red-300/80 font-semibold">Bulan ini</p>
                    <p class="text-base font-display font-bold text-white mt-0.5">{{ $rp($dp['this_month']['amount']) }}</p>
                    <p class="text-[11px] text-slate-500">{{ $dp['this_month']['count'] }} booking</p>
                </div>
                <div class="rounded-xl bg-white/[0.03] border border-white/10 p-3">
                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">6 bulan</p>
                    <p class="text-base font-display font-bold text-white mt-0.5">{{ $rp($dp['six_amount']) }}</p>
                    <p class="text-[11px] text-slate-500">{{ $dp['six_count'] }} booking</p>
                </div>
            </div>

            <div class="space-y-3">
                @foreach(array_reverse($dp['months']) as $m)
                    @php
                        $mw = $dp['max'] > 0 ? max($m['amount'] > 0 ? 3 : 0, round($m['amount'] / $dp['max'] * 100)) : 0;
                    @endphp
                    <div>
                        <div class="flex items-baseline justify-between gap-2 text-xs">
                            <span class="text-slate-300">{{ $m['label'] }}</span>
                            <span class="text-slate-500 whitespace-nowrap"><b class="text-slate-200">{{ $rp($m['amount']) }}</b> &middot; {{ $m['count'] }} booking</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-white/5 mt-1 overflow-hidden">
                            <div class="h-full rounded-full bg-red-400/80" style="width: {{ $mw }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-[11px] text-slate-500 mt-5 leading-relaxed">
                Sepanjang waktu: <b class="text-slate-300">{{ $rp($dp['all_amount']) }}</b> dari {{ $dp['all_count'] }} booking.
                <a href="{{ route('admin.bookings.index', ['status' => 'forfeited']) }}" class="text-electric hover:text-blue-300 font-medium">Lihat daftar &rarr;</a>
            </p>
        </div>
    </div>
</div>