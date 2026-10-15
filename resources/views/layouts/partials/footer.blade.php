<footer class="bg-ink text-slate-300 pt-16 pb-8 border-t border-white/5">
    <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-4 gap-12">

        <!-- Brand -->
        <div class="md:col-span-1">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center overflow-hidden">
                    <img src="{{ asset('images/logo.png') }}" alt="EHANZSTORE.ID" class="w-9 h-9 object-contain">
                </div>
                <span class="font-display font-bold text-white text-lg">EHANZSTORE</span>
            </div>
            <p class="text-sm leading-relaxed text-slate-400 mb-6">
                Marketplace jual beli akun Clash of Clans. Transaksi aman, akun dicek sebelum listing, dan ada garansi setelah pembelian.
            </p>
            <div class="flex gap-3">
                <a href="{{ $storeIgUrl }}" target="_blank" rel="noopener" aria-label="Instagram"
                   class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-pink-600 transition text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
                <a href="{{ $storeWaUrl }}" target="_blank" rel="noopener" aria-label="WhatsApp"
                   class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-green-600 transition text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                </a>
            </div>
        </div>

        <!-- Menu -->
        <div>
            <h3 class="text-white font-semibold mb-5">Menu</h3>
            <ul class="space-y-3 text-sm">
                <li><a href="{{ route('home') }}" class="text-slate-400 hover:text-white transition">Beranda</a></li>
                <li><a href="{{ route('catalog') }}" class="text-slate-400 hover:text-white transition">Katalog Akun</a></li>
                <li><a href="{{ route('how.to.buy') }}" class="text-slate-400 hover:text-white transition">Cara Membeli</a></li>
                <li><a href="{{ route('warranty') }}" class="text-slate-400 hover:text-white transition">Ketentuan Garansi</a></li>
            </ul>
        </div>

        <!-- Kontak -->
        <div class="md:col-span-2">
            <h3 class="text-white font-semibold mb-5">Kontak</h3>
            <div class="space-y-4 text-sm">
                <a href="{{ $storeWaUrl }}" target="_blank" rel="noopener" class="flex items-center gap-3 group">
                    <span class="w-9 h-9 rounded-lg bg-green-500/10 text-green-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </span>
                    <span>
                        <span class="block text-slate-500 text-xs">WhatsApp Admin</span>
                        <span class="text-white font-medium group-hover:text-electric transition">{{ $storeWaDisplay }}</span>
                    </span>
                </a>
                <a href="{{ $storeIgUrl }}" target="_blank" rel="noopener" class="flex items-center gap-3 group">
                    <span class="w-9 h-9 rounded-lg bg-pink-500/10 text-pink-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7a4 4 0 014-4zm5 5a4 4 0 100 8 4 4 0 000-8z"/></svg>
                    </span>
                    <span>
                        <span class="block text-slate-500 text-xs">Instagram</span>
                        <span class="text-white font-medium group-hover:text-electric transition">&#64;{{ $storeIg }}</span>
                    </span>
                </a>
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-lg bg-electric/10 text-electric flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    <span>
                        <span class="block text-slate-500 text-xs">Email Bisnis</span>
                        <span class="text-white font-medium">admin@ehanzstore.id</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 mt-12 pt-6 border-t border-white/5 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} EHANZSTORE.ID. Seluruh hak cipta dilindungi.
    </div>
</footer>