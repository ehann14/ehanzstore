<nav class="fixed w-full top-0 z-50 transition-all duration-300 border-b border-white/5 bg-brand-950/80 backdrop-blur-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20 items-center">
            
            <!-- Logo Area: Lebih Soft dengan Padding -->
            <a href="{{ route('home') }}" class="flex items-center gap-4 group">
                <div class="relative p-1 rounded-2xl bg-white/5 border border-white/10 group-hover:border-brand-500/50 transition-colors duration-300">
                    <img src="{{ asset('images/logo.png') }}" alt="EHANZ Logo" class="h-10 w-auto object-contain drop-shadow-md">
                </div>
                <div class="hidden md:flex flex-col">
                    <span class="text-white font-bold text-lg tracking-tight leading-none">EHANZSTORE</span>
                    <span class="text-brand-500 text-[10px] font-semibold tracking-widest uppercase opacity-80">Premium Market</span>
                </div>
            </a>
            
            <!-- Menu Desktop: Spacing Lebih Lega -->
            <div class="hidden md:flex space-x-10 items-center">
                @php $links = [['route'=>'home','label'=>'Beranda'], ['route'=>'catalog','label'=>'Katalog'], ['route'=>'how.to.buy','label'=>'Cara Beli']]; @endphp
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}" 
                       class="text-sm font-medium text-slate-400 hover:text-white transition-colors relative py-2 {{ request()->routeIs($link['route']) ? 'text-white' : '' }}">
                        {{ $link['label'] }}
                        @if(request()->routeIs($link['route']))
                            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-500 rounded-full shadow-[0_0_10px_rgba(59,130,246,0.5)]"></span>
                        @endif
                    </a>
                @endforeach
            </div>

            <!-- CTA Button: Rounded Full & Gradient Subtle -->
            <a href="https://wa.me/6282130559126" target="_blank" 
               class="hidden sm:flex items-center gap-2.5 bg-brand-600 hover:bg-brand-500 text-white px-6 py-2.5 rounded-full text-sm font-semibold transition-all shadow-[0_0_20px_rgba(37,99,235,0.3)] hover:shadow-[0_0_30px_rgba(37,99,235,0.5)] hover:-translate-y-0.5 border border-brand-400/20">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                Hubungi Admin
            </a>
        </div>
    </div>
</nav>