<div class="glass-panel rounded-2xl p-5 sm:p-6 mb-6 {{ $warrantyAlerts->isNotEmpty() ? 'border border-amber-500/30' : '' }}">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div class="flex items-start gap-3">
                <div class="p-2 rounded-xl {{ $warrantyAlerts->isNotEmpty() ? 'bg-amber-500/15 text-amber-300' : 'bg-emerald-500/10 text-emerald-300' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-white">Pengingat Garansi</h3>
                    <p class="text-xs text-slate-500">Garansi yang berakhir dalam 2 hari ke depan</p>
                </div>
            </div>
            @if($warrantyAlerts->isNotEmpty())
                <span class="shrink-0 px-2.5 py-1 rounded-full border text-[11px] font-semibold bg-amber-500/15 text-amber-300 border-amber-500/30">{{ $warrantyAlerts->count() }} akun</span>
            @endif
        </div>

        @if($warrantyAlerts->isEmpty())
            <p class="text-sm text-slate-500 py-2">Tidak ada garansi yang akan berakhir dalam 2 hari ke depan.</p>
        @else
            <div class="divide-y divide-white/5">
                @foreach($warrantyAlerts as $alert)
                    @php
                        $acc   = $alert->account;
                        $waMsg = 'Halo ' . ($alert->buyer_name ?: 'kak') . ', garansi akun ' . ($acc?->name ?? $alert->account_code_snapshot)
                               . ' (' . $alert->account_code_snapshot . ') di EHANZSTORE.ID akan berakhir pada ' . $alert->warrantyUntilLabel()
                               . '. Kalau ada kendala dengan akunnya, silakan hubungi kami sebelum garansi habis ya.';
                        $waUrl = $alert->whatsappUrl($waMsg);
                    @endphp
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white font-medium truncate">{{ $acc?->name ?? 'Akun dihapus' }}
                                <span class="text-[11px] text-slate-500 font-mono ml-1">{{ $alert->account_code_snapshot }}</span>
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Pembeli: {{ $alert->buyer_name ?: 'belum diisi' }}@if($alert->localPhone()) &middot; {{ $alert->localPhone() }}@endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3 sm:justify-end shrink-0">
                            <div class="sm:text-right">
                                <span {!! $alert->countdownAttrs('Garansi sisa ', 'Garansi habis', true) !!} class="inline-flex whitespace-nowrap px-2.5 py-0.5 rounded-full border text-[11px] font-semibold bg-amber-500/15 text-amber-300 border-amber-500/30">Garansi {{ $alert->warrantyRemainingText() }}</span>
                                <p class="text-[11px] text-slate-500 mt-1 whitespace-nowrap">s/d {{ $alert->warrantyUntilLabel() }}</p>
                            </div>
                            @if($waUrl)
                                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-green-600 hover:bg-green-500 text-white text-xs font-semibold transition whitespace-nowrap">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                                    Ingatkan
                                </a>
                            @else
                                <a href="{{ route('admin.sales.buyer', $alert) }}" class="text-xs text-electric hover:underline whitespace-nowrap">Isi no. WA</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>