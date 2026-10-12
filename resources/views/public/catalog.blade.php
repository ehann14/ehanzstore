@extends('layouts.app')

@section('title', 'Katalog Akun COC - EHANZSTORE.ID')

@section('content')
    <!-- Header Katalog -->
    <section class="bg-brand-navy pt-32 pb-16 border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">Katalog Akun Premium</h1>
            <p class="text-blue-200 max-w-2xl mx-auto">Pilih akun impianmu dari koleksi terbaik kami. Semua akun telah diverifikasi dan siap pakai.</p>
        </div>
    </section>

    <!-- Main Content Area -->
    <div class="max-w-7xl mx-auto px-4 py-12 flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Filters (Desktop) & Mobile Filter Toggle -->
        <aside class="w-full lg:w-64 shrink-0 space-y-6">
            <!-- Mobile Filter Button -->
            <button onclick="document.getElementById('mobile-filters').classList.toggle('hidden')" 
                    class="lg:hidden w-full bg-white border border-gray-200 p-3 rounded-xl font-semibold text-gray-700 flex items-center justify-center gap-2 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                Filter & Urutkan
            </button>

            <!-- Filter Form Container -->
            <div id="mobile-filters" class="hidden lg:block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                
                <!-- Search -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Cari Akun</label>
                    <form action="{{ route('catalog') }}" method="GET" class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau Kode..." 
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand focus:border-transparent outline-none transition text-sm">
                        <svg class="w-4 h-4 absolute left-3 top-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <!-- Preserve other params when searching -->
                        @foreach(request()->except('search', 'page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                    </form>
                </div>

                <!-- Town Hall Filter -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Town Hall</label>
                    <select onchange="this.form.submit()" name="th" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand outline-none text-sm cursor-pointer">
                        <option value="">Semua Level TH</option>
                        @for($i = 9; $i <= 16; $i++)
                            <option value="{{ $i }}" {{ request('th') == $i ? 'selected' : '' }}>TH {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Sort By -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Urutkan Berdasarkan</label>
                    <select onchange="this.form.submit()" name="sort" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand outline-none text-sm cursor-pointer">
                        <option value="">Terbaru Ditambahkan</option>
                        <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Harga: Rendah ke Tinggi</option>
                        <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Harga: Tinggi ke Rendah</option>
                    </select>
                </div>

                <!-- Reset Button -->
                @if(request()->anyFilled(['search', 'th', 'sort']))
                    <a href="{{ route('catalog') }}" class="block w-full text-center py-2 text-sm text-red-500 hover:text-red-700 font-medium border border-red-100 rounded-lg hover:bg-red-50 transition">
                        Reset Filter
                    </a>
                @endif
            </div>
        </aside>

        <!-- Product Grid -->
        <div class="flex-grow">
            <div class="mb-6 flex justify-between items-center">
                <p class="text-gray-500 text-sm">Menampilkan <span class="font-bold text-brand-navy">{{ $accounts->total() }}</span> akun tersedia</p>
            </div>

            @if($accounts->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($accounts as $account)
                        @include('components.account-card', ['account' => $account])
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12">
                    {{ $accounts->links('vendor.pagination.tailwind') }}
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white rounded-2xl p-12 text-center border border-dashed border-gray-300">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Akun Tidak Ditemukan</h3>
                    <p class="text-gray-500 text-sm mb-6">Coba ubah kata kunci pencarian atau reset filter kamu.</p>
                    <a href="{{ route('catalog') }}" class="inline-block bg-brand text-white px-6 py-2.5 rounded-full text-sm font-semibold hover:bg-brand-dark transition">Lihat Semua Akun</a>
                </div>
            @endif
        </div>
    </div>
@endsection