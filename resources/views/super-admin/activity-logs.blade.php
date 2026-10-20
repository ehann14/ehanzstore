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

    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Riwayat Aktivitas</h1>
        <p class="text-sm text-slate-400 mt-1">Catatan tindakan yang dilakukan admin di panel kontrol.</p>
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

                    <div class="text-left sm:text-right shrink-0">
                        <p class="text-sm text-slate-300">{{ $log->created_at->clone()->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $log->created_at->diffForHumans() }}
                            @if(!empty($meta['ip']))
                                · IP {{ $meta['ip'] }}
                            @endif
                        </p>
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
@endsection