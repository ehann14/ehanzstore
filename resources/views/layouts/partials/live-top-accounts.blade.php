@forelse($topAccounts as $i => $item)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5 hover:border-electric/30 transition">
                        <span class="w-7 h-7 shrink-0 rounded-lg bg-electric/15 text-electric text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-white truncate">{{ $item->account?->name ?? 'Akun dihapus' }}</p>
                            <p class="text-[11px] text-slate-500 font-mono">
                                {{ $item->account?->account_code ?? '-' }}
                                @if($item->account) &middot; {{ $item->account->badgeLabel() }} @endif
                            </p>
                        </div>
                        <span class="shrink-0 bg-electric/15 text-brand-300 text-xs font-bold px-2 py-1 rounded-lg">{{ $item->views }}x</span>
                    </div>
                @empty
                    <div class="text-center py-12 text-slate-500 text-sm">Belum ada akun yang dilihat pengunjung.</div>
                @endforelse