<div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden group flex flex-col h-full">
    <!-- Image Wrapper -->
    <div class="relative h-56 overflow-hidden bg-gray-100">
        @if($account->primaryImage)
            <img src="{{ Storage::url($account->primaryImage->image_path) }}" 
                 alt="{{ $account->name }}" 
                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
        @else
            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span class="text-xs">No Image</span>
            </div>
        @endif
        
        <!-- Badges -->
        <div class="absolute top-3 left-3 flex gap-2">
            <span class="bg-brand-navy/90 backdrop-blur text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm">
                TH {{ $account->town_hall }}
            </span>
            @if($account->sale_status === 'sold')
                <span class="bg-red-500/90 backdrop-blur text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm">TERJUAL</span>
            @endif
        </div>
    </div>

    <!-- Content -->
    <div class="p-6 flex flex-col flex-grow">
        <div class="flex justify-between items-start mb-2">
            <h3 class="font-bold text-lg text-gray-900 line-clamp-1">{{ $account->name }}</h3>
        </div>
        
        <p class="text-xs text-gray-400 font-mono mb-4 bg-gray-50 inline-block px-2 py-1 rounded w-fit">CODE: {{ $account->account_code }}</p>
        
        <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">Harga</p>
                <p class="text-xl font-bold text-brand">Rp {{ number_format($account->price, 0, ',', '.') }}</p>
            </div>
            <a href="{{ route('account.detail', $account->account_code) }}" 
               class="bg-gray-900 text-white p-3 rounded-xl hover:bg-brand transition-colors shadow-lg shadow-gray-900/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </div>
</div>