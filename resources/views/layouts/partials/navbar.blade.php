<nav class="fixed top-0 left-0 right-0 z-50 bg-midnight/90 backdrop-blur-md border-b border-white/5 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            
            <!-- LOGO AREA (Pastikan path benar) -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 group shrink-0">
                <div class="relative w-12 h-12 flex items-center justify-center bg-white rounded-xl overflow-hidden shadow-lg group-hover:shadow-electric/20 transition-all duration-300">
                    <!-- GANTI 'logo.png' SESUAI NAMA FILE ASLI ANDA -->
                    <img src="{{ asset('images/logo.png') }}" 
                         alt="EHANZSTORE Logo" 
                         class="w-10 h-10 object-contain p-0.5"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    
                    <!-- Fallback jika gambar error/hilang -->
                    <div class="hidden absolute inset-0 bg-electric text-white font-display font-bold text-xl items-center justify-center">E</div>
                </div>
                
                <div class="hidden md:flex flex-col leading-none">
                    <span class="font-display font-bold text-lg text-white tracking-tight">EHANZSTORE</span>
                    <span class="text-[10px] text-slate-400 font-medium tracking-widest uppercase mt-0.5">Marketplace COC</span>
                </div>
            </a>
            
            <!-- DESKTOP NAVIGATION -->
            <div class="hidden md:flex items-center gap-8">
                @php 
                    $links = [
                        ['route' => 'home', 'label' => 'Beranda'], 
                        ['route' => 'catalog', 'label' => 'Katalog'], 
                        ['route' => 'how.to.buy', 'label' => 'Cara Beli']
                    ]; 
                @endphp
                
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}" 
                       class="text-sm font-medium transition-colors duration-200 {{ request()->routeIs($link['route']) ? 'text-electric' : 'text-slate-400 hover:text-white' }}">
                       {{ $link['label'] }}
                    </a>
                @endforeach
            </div>

            <!-- CTA BUTTON -->
            <div class="flex items-center gap-4">
                <a href="https://wa.me/6282130559126" target="_blank" 
                   class="hidden sm:flex items-center gap-2 bg-electric hover:bg-blue-500 text-white px-5 py-2.5 rounded-full text-sm font-bold transition-all shadow-[0_0_20px_rgba(59,130,246,0.3)] hover:shadow-[0_0_30px_rgba(59,130,246,0.5)] hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                    Chat Admin
                </a>
                
                <!-- Mobile Menu Button -->
                <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="md:hidden text-slate-300 hover:text-white p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- MOBILE MENU DROPDOWN -->
    <div id="mobileMenu" class="hidden md:hidden bg-midnight border-t border-white/5 absolute w-full left-0 top-20 shadow-2xl">
        <div class="px-4 py-6 space-y-4">
            @foreach($links as $link)
                <a href="{{ route($link['route']) }}" 
                   class="block text-base font-medium {{ request()->routeIs($link['route']) ? 'text-electric' : 'text-slate-300' }}">
                   {{ $link['label'] }}
                </a>
            @endforeach
            <a href="https://wa.me/6282130559126" target="_blank" class="block w-full text-center bg-electric text-white py-3 rounded-xl font-bold mt-4">
                Hubungi Admin via WA
            </a>
        </div>
    </div>
</nav>