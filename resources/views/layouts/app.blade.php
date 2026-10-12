<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'EHANZSTORE.ID - Premium COC Marketplace')</title>
    
    <!-- Font: Space Grotesk (Futuristik) + Inter (Readable) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind Config -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        midnight: '#020617', // Background Utama
                        royal: '#0645AD',    // Warna Logo
                        electric: '#3b82f6', // Aksen Terang
                        surface: 'rgba(15, 23, 42, 0.6)', // Glass Base
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        body { background-color: #020617; color: #e2e8f0; }
        
        /* Utility Glassmorphism */
        .glass { 
            background: rgba(15, 23, 42, 0.4); 
            backdrop-filter: blur(16px); 
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08); 
        }
        .glass-strong {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Glow Effects */
        .glow-text { text-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
        .glow-box { box-shadow: 0 0 40px -10px rgba(6, 69, 173, 0.3); }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #020617; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #3b82f6; }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col selection:bg-electric selection:text-white">
    
    @include('layouts.partials.navbar')

    <main class="flex-grow relative z-10">
        @yield('content')
    </main>

    @include('layouts.partials.footer')
    @stack('scripts')
</body>
</html>