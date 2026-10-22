{{--
    Kartu pengingat stok lama untuk dashboard.
    Admin biasa: hanya akun miliknya. Super Admin: semua akun.
--}}
@php
    $staleSummary = \App\Models\CocAccount::staleSummary(auth()->user()->isSuperAdmin() ? null : auth()->id());
    $staleDays    = (int) config('store.stale_days', 30);
@endphp

@if($staleSummary['count'] > 0)
    <a href="{{ route('admin.stale.index') }}"
       class="glass-panel rounded-2xl p-5 sm:p-6 mb-6 flex flex-col sm:flex-row sm:items-center gap-4 border border-amber-500/30 hover:border-amber-500/60 transition-colors group">
        <div class="flex items-start gap-3 flex-1 min-w-0">
            <div class="p-2 rounded-xl bg-amber-500/15 text-amber-300 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <h3 class="font-bold text-white">{{ $staleSummary['count'] }} stok belum laku lebih dari {{ $staleDays }} hari</h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Yang terlama sudah {{ $staleSummary['oldest_days'] }} hari.
                    @if($staleSummary['capital'] > 0)
                        Modal tertahan Rp {{ number_format($staleSummary['capital'], 0, ',', '.') }}.
                    @endif
                    Lihat saran penurunan harganya.
                </p>
            </div>
        </div>
        <span class="shrink-0 inline-flex items-center gap-2 text-sm font-semibold text-amber-300 group-hover:text-white transition">
            Buka daftar
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </span>
    </a>
@endif