<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'EHANZSTORE.ID - Premium COC Marketplace')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS & Config for Brand Colors -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            light: '#3882F6', // Electric Blue
                            DEFAULT: '#0645AD', // Royal Blue (Logo)
                            dark: '#123B8A', // Deep Blue
                            navy: '#0B1220', // Midnight Navy (Background)
                        }
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script src="//unpkg.com/alpinejs" defer></script>
    
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .glass-nav { background: rgba(11, 18, 32, 0.9); backdrop-filter: blur(10px); }
        .hero-gradient { background: linear-gradient(135deg, #0B1220 0%, #0645AD 100%); }
    </style>
</head>
<body class="bg-gray-50 text-slate-800 antialiased flex flex-col min-h-screen">
    
    @include('layouts.partials.navbar')

    <main class="flex-grow">
        @yield('content')
    </main>

    @include('layouts.partials.footer')

    @stack('scripts')
</body>
</html>