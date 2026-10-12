<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'EHANZSTORE.ID - Premium COC Marketplace')</title>
    
    <!-- Fonts: Plus Jakarta Sans (Lebih Modern & Geometris dari Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind Config for Smooth UI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6', // Electric Blue yang lebih soft
                            600: '#2563eb', // Primary Action
                            700: '#1d4ed8', // Deep Blue
                            900: '#0f172a', // Midnight Navy (Background)
                            950: '#020617', // Darker Depth
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    borderRadius: {
                        '4xl': '2rem', // Sudut sangat bulat untuk kesan modern
                    }
                }
            }
        }
    </script>
    
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(16px); 
            border: 1px solid rgba(255, 255, 255, 0.08); 
        }
        .hero-glow {
            background: radial-gradient(circle at center, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
        }
    </style>
</head>
<body class="bg-brand-950 text-slate-200 antialiased flex flex-col min-h-screen selection:bg-brand-500 selection:text-white">
    
    @include('layouts.partials.navbar')

    <main class="flex-grow relative z-10">
        @yield('content')
    </main>

    @include('layouts.partials.footer')

    @stack('scripts')
</body>
</html>