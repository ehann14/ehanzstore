<div class="group relative glass rounded-3xl overflow-hidden hover:border-electric/40 transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_20px_50px_-12px_rgba(59,130,246,0.2)] flex flex-col h-full">
    <!-- Image Area -->
    <div class="relative h-56 overflow-hidden bg-midnight">
        @if($account->primaryImage)
            <img src="{{ Storage::url($account->primaryImage->image_path) }}" 
                 alt="{{ $account->name }}" 
                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 opacity-90 group-hover:opacity-100">
            <div class="absolute inset-0 bg-gradient-to-t from-midnight via-midnight/50 to-transparent opacity-90"></div>
        @else
            <div class="w-full h-full flex flex-col items-center justify-center text-slate-700 bg-slate-900/50">
                <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
        @endif
        
        <div class="absolute top-4 left-4">
            <span class="px-3 py-1.5 bg-electric/90 backdrop-blur text-white text-[10px] font-bold rounded-lg shadow-lg shadow-electric/30 border border-white/20 uppercase tracking-wider">
                Town Hall {{ $account->town_hall }}
            </span>
        </div>
    </div>

    <!-- Content -->
    <div class="p-5 flex flex-col flex-grow relative z-10">
        <h3 class="font-display font-bold text-lg text-white mb-1 line-clamp-1 group-hover:text-electric transition-colors">{{ $account->name }}</h3>
        <p class="text-[10px] font-mono text-slate-500 bg-white/5 inline-block px-2 py-1 rounded border border-white/5 w-fit mb-4">CODE: {{ $account->account_code }}</p>
        
        <div class="mt-auto pt-4 border-t border-white/5 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5">Harga</p>
                <p class="text-xl font-display font-bold text-white">Rp {{ number_format($account->price, 0, ',', '.') }}</p>
            </div>
            <a href="{{ route('account.detail', $account->account_code) }}" 
               class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-slate-300 transition-all group-hover:bg-electric group-hover:text-white group-hover:border-electric group-hover:rotate-45">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </div>
</div>