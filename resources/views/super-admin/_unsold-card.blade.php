{{--
    Kartu ringkas akun belum terjual untuk dashboard Super Admin.
    Belum terjual = sale_status 'available' (terbit maupun draft).
--}}
@php
    $unsoldRow = \App\Models\CocAccount::where('sale_status', 'available')
        ->selectRaw('COUNT(*) as total, COALESCE(SUM(price), 0) as total_price')
        ->first();
    $unsoldCount = (int) ($unsoldRow->total ?? 0);
    $unsoldPrice = (float) ($unsoldRow->total_price ?? 0);
@endphp

<a href="{{ route('super-admin.unsold') }}"
   class="glass-panel rounded-2xl p-5 sm:p-6 mb-6 flex flex-col sm:flex-row sm:items-center gap-4 border border-electric/30 hover:border-electric/60 transition-colors group">
    <div class="flex items-start gap-3 flex-1 min-w-0">
        <div class="p-2 rounded-xl bg-electric/15 text-electric shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <div class="min-w-0">
            <h3 class="font-bold text-white">{{ number_format($unsoldCount) }} akun belum terjual</h3>
            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                Total harga jual Rp {{ number_format($unsoldPrice, 0, ',', '.') }}. Klik untuk melihat daftar lengkap beserta modal dan estimasi laba.
            </p>
        </div>
    </div>
    <span class="shrink-0 inline-flex items-center gap-2 text-sm font-semibold text-electric group-hover:text-white transition">
        Lihat daftar
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </span>
</a>