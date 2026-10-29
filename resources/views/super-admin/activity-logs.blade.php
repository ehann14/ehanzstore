@extends('layouts.admin')

@section('title', 'Riwayat Aktivitas')

@section('content')
@php
    $tones = [
        'slate' => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
        'green' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
        'blue'  => 'bg-electric/15 text-blue-300 border-electric/30',
        'amber' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        'red'   => 'bg-red-500/15 text-red-300 border-red-500/30',
    ];

    $fieldClass = 'w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-electric';
    $hasFilter  = request()->hasAny(['user_id', 'action', 'from', 'to']);
@endphp

<div class="max-w-5xl mx-auto pb-16">

    <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Riwayat Aktivitas</h1>
            <p class="text-sm text-slate-400 mt-1">Catatan tindakan yang dilakukan admin di panel kontrol.</p>
        </div>
        @if($stats['total'] > 0)
            <button type="button" onclick="openClearLogs()"
                    class="self-start sm:self-auto inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-red-300 text-sm font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Bersihkan Riwayat
            </button>
        @endif
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ number_format($stats['total'], 0, ',', '.') }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Total Catatan</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ number_format($stats['today'], 0, ',', '.') }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Hari Ini</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ $stats['active_today'] }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Pengguna Aktif Hari Ini</p>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" action="{{ route('super-admin.activity-logs') }}" class="glass-panel rounded-2xl p-4 sm:p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] text-slate-500 uppercase tracking-wider font-semibold mb-1.5">Pengguna</label>
                <select name="user_id" class="{{ $fieldClass }}">
                    <option value="" class="bg-slate-900">Semua</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" class="bg-slate-900" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] text-slate-500 uppercase tracking-wider font-semibold mb-1.5">Aksi</label>
                <select name="action" class="{{ $fieldClass }}">
                    <option value="" class="bg-slate-900">Semua</option>
                    @foreach(\App\Models\ActivityLog::ACTIONS as $key => $def)
                        <option value="{{ $key }}" class="bg-slate-900" @selected(request('action') === $key)>{{ $def['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] text-slate-500 uppercase tracking-wider font-semibold mb-1.5">Dari tanggal</label>
                <input type="date" name="from" value="{{ request('from') }}" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="block text-[11px] text-slate-500 uppercase tracking-wider font-semibold mb-1.5">Sampai tanggal</label>
                <input type="date" name="to" value="{{ request('to') }}" class="{{ $fieldClass }}">
            </div>
        </div>

        <div class="flex items-center gap-3 mt-4">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-semibold transition">
                Terapkan
            </button>
            @if($hasFilter)
                <a href="{{ route('super-admin.activity-logs') }}" class="text-sm text-slate-400 hover:text-white transition">Reset filter</a>
            @endif
        </div>
    </form>

    <!-- Daftar -->
    @if($logs->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center">
            <p class="text-slate-300 font-semibold">Belum ada aktivitas yang tercatat.</p>
            <p class="text-sm text-slate-500 mt-1">
                @if($hasFilter)
                    Coba ubah atau reset filter.
                @else
                    Aktivitas baru akan muncul di sini setelah admin melakukan tindakan.
                @endif
            </p>
        </div>
    @else
        <!-- Aksi massal -->
        <div class="flex items-center gap-3 mb-3 px-1">
            <label class="inline-flex items-center gap-2 text-xs text-slate-400 cursor-pointer select-none">
                <input type="checkbox" id="checkAllLogs" class="w-4 h-4 rounded accent-red-500"> Pilih semua di halaman ini
            </label>
            <span id="selectedCount" class="text-xs text-slate-500"></span>
            <button type="button" id="bulkDeleteBtn" onclick="deleteSelected()" disabled
                    class="ml-auto px-4 py-2 rounded-full bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition disabled:opacity-30 disabled:cursor-not-allowed">
                Hapus Terpilih
            </button>
        </div>

        <div class="space-y-3">
            @foreach($logs as $log)
                @php
                    $def   = \App\Models\ActivityLog::ACTIONS[$log->action] ?? ['label' => ucfirst(str_replace('_', ' ', $log->action)), 'tone' => 'slate'];
                    $meta  = $log->metadata ?? [];
                    $parts = [];

                    if (!empty($meta['changed'])) {
                        $parts[] = 'Kolom diubah: ' . implode(', ', $meta['changed']);
                    }
                    if (!empty($meta['changes']) && is_array($meta['changes'])) {
                        foreach ($meta['changes'] as $field => $value) {
                            $parts[] = $field . ' → ' . $value;
                        }
                    }
                    if (isset($meta['price'])) {
                        $parts[] = 'Harga Rp ' . number_format((float) $meta['price'], 0, ',', '.');
                    }
                    if (array_key_exists('is_active', $meta)) {
                        $parts[] = $meta['is_active'] ? 'Ditampilkan' : 'Disembunyikan';
                    }
                    $name = $log->user?->name ?? 'Pengguna dihapus';
                @endphp

                <div class="glass-panel rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex items-center gap-4 min-w-0 flex-1">
                        <input type="checkbox" name="ids[]" value="{{ $log->id }}" form="bulkForm" class="log-check w-4 h-4 shrink-0 rounded accent-red-500" aria-label="Pilih catatan">
                        <div class="w-11 h-11 shrink-0 rounded-full flex items-center justify-center text-white font-bold bg-gradient-to-br from-slate-600 to-slate-500">
                            {{ strtoupper(mb_substr($name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-white truncate">{{ $name }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-medium {{ $tones[$def['tone']] ?? $tones['slate'] }}">
                                    {{ $def['label'] }}
                                </span>
                            </div>
                            @if(!empty($meta['label']))
                                <p class="text-sm text-slate-300 truncate">{{ $meta['label'] }}</p>
                            @endif
                            @if($parts)
                                <p class="text-xs text-slate-500 mt-0.5 break-words">{{ implode(' · ', $parts) }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3 sm:justify-end shrink-0">
                        <div class="text-left sm:text-right">
                            <p class="text-sm text-slate-300">{{ $log->created_at->clone()->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $log->created_at->diffForHumans() }}
                                @if(!empty($meta['ip']))
                                    · IP {{ $meta['ip'] }}
                                @endif
                            </p>
                        </div>
                        <button type="button" onclick="deleteOne('{{ route('super-admin.activity-logs.destroy', $log) }}')"
                                class="ml-auto sm:ml-0 w-9 h-9 shrink-0 rounded-full bg-white/5 hover:bg-red-500/20 text-slate-400 hover:text-red-300 flex items-center justify-center transition" aria-label="Hapus catatan" title="Hapus catatan ini">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="flex items-center justify-between mt-6">
                <span class="text-xs text-slate-500">Halaman {{ $logs->currentPage() }} dari {{ $logs->lastPage() }}</span>
                <div class="flex gap-2">
                    @if($logs->onFirstPage())
                        <span class="px-4 py-2 rounded-xl bg-white/5 text-slate-600 text-sm cursor-not-allowed">← Sebelumnya</span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-200 text-sm transition">← Sebelumnya</a>
                    @endif

                    @if($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-200 text-sm transition">Berikutnya →</a>
                    @else
                        <span class="px-4 py-2 rounded-xl bg-white/5 text-slate-600 text-sm cursor-not-allowed">Berikutnya →</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>

{{-- Form tersembunyi untuk hapus satu / terpilih (tombol & checkbox di atas memakai atribut form=...) --}}
<form id="bulkForm" method="POST" action="{{ route('super-admin.activity-logs.bulk-delete') }}" class="hidden">@csrf</form>
<form id="singleForm" method="POST" class="hidden">@csrf @method('DELETE')</form>

<!-- Modal bersihkan riwayat -->
<div id="clearLogsModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <form method="POST" action="{{ route('super-admin.activity-logs.clear') }}" class="glass-strong w-full max-w-md rounded-3xl p-7 border border-white/10 shadow-2xl max-h-[92vh] overflow-y-auto">
        @csrf
        @foreach(['user_id', 'action', 'from', 'to'] as $f)
            @if(request()->filled($f))<input type="hidden" name="{{ $f }}" value="{{ request($f) }}">@endif
        @endforeach

        <div class="w-12 h-12 bg-red-500/10 rounded-full flex items-center justify-center mb-4 text-red-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        </div>
        <h3 class="text-xl font-display font-bold text-white mb-1">Bersihkan Riwayat Aktivitas</h3>
        <p class="text-sm text-slate-400 mb-5">Catatan yang dihapus tidak bisa dikembalikan. Satu catatan baru akan dibuat untuk mencatat pembersihan ini.</p>

        <div class="space-y-2 mb-5">
            @php
                $scopes = [
                    'older_90' => 'Lebih lama dari 90 hari',
                    'older_30' => 'Lebih lama dari 30 hari',
                    'older_7'  => 'Lebih lama dari 7 hari',
                ];
                if ($hasFilter) { $scopes['filtered'] = 'Hanya yang sesuai filter saat ini'; }
                $scopes['all'] = 'Semua catatan';
            @endphp
            @foreach($scopes as $val => $text)
                <label class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-white/10 bg-white/[0.03] cursor-pointer text-sm text-slate-200 has-[:checked]:border-red-500/40 has-[:checked]:bg-red-500/5">
                    <input type="radio" name="scope" value="{{ $val }}" class="accent-red-500" @checked($loop->first) required> {{ $text }}
                </label>
            @endforeach
        </div>

        <label for="clearPassword" class="block text-xs font-semibold text-slate-400 mb-2">Konfirmasi dengan password kamu</label>
        <input id="clearPassword" type="password" name="password" required autocomplete="current-password" placeholder="Password Super Admin"
               class="w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-red-500 mb-5">

        <div class="flex gap-3">
            <button type="button" onclick="closeClearLogs()" class="flex-1 py-3 rounded-full border border-white/10 text-slate-300 hover:bg-white/5 transition">Batal</button>
            <button type="submit" class="flex-1 py-3 rounded-full bg-red-600 hover:bg-red-500 text-white font-bold transition">Hapus</button>
        </div>
    </form>
</div>

<script>
    (function () {
        var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('.log-check')); };
        var all = document.getElementById('checkAllLogs');
        var btn = document.getElementById('bulkDeleteBtn');
        var counter = document.getElementById('selectedCount');

        function refresh() {
            var n = checks().filter(function (c) { return c.checked; }).length;
            if (btn) { btn.disabled = n === 0; }
            if (counter) { counter.textContent = n ? n + ' dipilih' : ''; }
            if (all) { all.checked = n > 0 && n === checks().length; }
        }

        if (all) {
            all.addEventListener('change', function () {
                checks().forEach(function (c) { c.checked = all.checked; });
                refresh();
            });
        }
        checks().forEach(function (c) { c.addEventListener('change', refresh); });
        refresh();

        window.deleteSelected = function () {
            var n = checks().filter(function (c) { return c.checked; }).length;
            if (n && confirm('Hapus ' + n + ' catatan terpilih? Tindakan ini tidak bisa dibatalkan.')) {
                document.getElementById('bulkForm').submit();
            }
        };

        window.deleteOne = function (url) {
            if (confirm('Hapus catatan aktivitas ini?')) {
                var f = document.getElementById('singleForm');
                f.action = url;
                f.submit();
            }
        };

        var modal = document.getElementById('clearLogsModal');
        window.openClearLogs = function () { modal.classList.remove('hidden'); modal.classList.add('flex'); };
        window.closeClearLogs = function () { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        modal.addEventListener('click', function (e) { if (e.target === modal) { closeClearLogs(); } });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeClearLogs(); } });
    })();
</script>
@endsection