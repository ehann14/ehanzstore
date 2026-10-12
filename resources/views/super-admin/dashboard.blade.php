@extends('layouts.admin')

@section('title', 'Super Admin Dashboard')

@section('content')
    <!-- Kartu Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-sm font-medium">Total Pengunjung</h3>
            <p class="text-2xl font-bold text-gray-800">{{ $totalVisitors }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <h3 class="text-gray-500 text-sm font-medium">Pengunjung Hari Ini</h3>
            <p class="text-2xl font-bold text-gray-800">{{ $todayVisitors }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
            <h3 class="text-gray-500 text-sm font-medium">Akun Tersedia</h3>
            <p class="text-2xl font-bold text-gray-800">{{ $availableAccounts }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
            <h3 class="text-gray-500 text-sm font-medium">Akun Terjual</h3>
            <p class="text-2xl font-bold text-gray-800">{{ $soldAccounts }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Grafik Pengunjung -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Statistik Pengunjung (7 Hari)</h3>
            <canvas id="visitorChart"></canvas>
        </div>

        <!-- Akun Populer -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Akun Paling Sering Dilihat</h3>
            <ul class="space-y-3">
                @foreach($topAccounts as $item)
                    <li class="flex justify-between items-center border-b pb-2">
                        <div>
                            <span class="font-semibold text-blue-600">{{ $item->account?->account_code }}</span>
                            <span class="text-sm text-gray-600 ml-2">{{ $item->account?->name }}</span>
                        </div>
                        <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">{{ $item->views }} views</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('visitorChart').getContext('2d');
        const visitorData = @json($visitorStats);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: visitorData.map(item => item.date),
                datasets: [{
                    label: 'Jumlah Pengunjung',
                    data: visitorData.map(item => item.count),
                    borderColor: '#0645AD',
                    backgroundColor: 'rgba(6, 69, 173, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
    @endpush
@endsection