<div class="group glass-panel rounded-4xl overflow-hidden hover:border-brand-500/30 transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.5)] flex flex-col h-full">
    <!-- Image Area with Soft Overlay -->
    <div class="relative h-64 overflow-hidden bg-brand-900/50">
        @if($account->primaryImage)
            <img src="{{ Storage::url($account->primaryImage->image_path) }}" 
                 alt="{{ $account->name }}" 
                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 opacity-90 group-hover:opacity-100">
        @else
            <div class="w-full h-full flex flex-col items-center justify-center text-slate-600">
                <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
        @endif
        
        <!-- Soft Badge -->
        <div class="absolute top-4 left-4">
            <span class="px-3 py-1.5 bg-black/40 backdrop-blur-md border border-white/10 text-white text-xs font-bold rounded-full shadow-lg">
                TH {{ $account->town_hall }}
            </span>
        </div>
    </div>

    <!-- Content Area -->
    <div class="p-6 flex flex-col flex-grow">
        <h3 class="font-bold text-xl text-white mb-2 line-clamp-1 group-hover:text-brand-400 transition-colors">{{ $account->name }}</h3>
        
        <div class="flex items-center gap-2 mb-6">
            <span class="text-[10px] font-mono text-slate-500 bg-white/5 px-2 py-1 rounded-lg border border-white/5">ID: {{ $account->account_code }}</span>
        </div>
        
        <div class="mt-auto pt-4 border-t border-white/5 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-1">Harga Mulai</p>
                <p class="text-2xl font-bold text-white">Rp {{ number_format($account->price, 0, ',', '.') }}</p>
            </div>
            <a href="{{ route('account.detail', $account->account_code) }}" 
               class="w-12 h-12 rounded-full bg-brand-600 hover:bg-brand-500 text-white flex items-center justify-center shadow-lg shadow-brand-600/20 transition-all group-hover:scale-110">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </div>
</div>