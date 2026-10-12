<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EHANZSTORE.ID</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="//unpkg.com/alpinejs" defer></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-[#0B1220] text-white hidden md:block">
            <div class="p-6 font-bold text-xl border-b border-gray-800">EHANZ ADMIN</div>
            <nav class="mt-6">
                <a href="{{ route('admin.dashboard') }}" class="block py-3 px-6 hover:bg-blue-900 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-900' : '' }}">Dashboard</a>
                <a href="{{ route('admin.accounts.index') }}" class="block py-3 px-6 hover:bg-blue-900 {{ request()->routeIs('admin.accounts.*') ? 'bg-blue-900' : '' }}">Kelola Akun</a>
                <form action="{{ route('logout') }}" method="POST" class="mt-10 px-6">
                    @csrf
                    <button type="submit" class="text-red-400 hover:text-red-300 text-sm">Logout</button>
                </form>
            </nav>
        </aside>

        <!-- Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow p-4 flex justify-between items-center">
                <h2 class="font-semibold text-gray-700">@yield('title', 'Dashboard')</h2>
                <div class="text-sm text-gray-500">{{ Auth::user()->name }}</div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        {{ session('success') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>