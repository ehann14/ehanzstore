@props(['social' => null, 'noun' => 'akun'])
{{-- Social proof halaman detail. Pakai: <x-social-proof :social="$social" :noun="$noun" /> --}}
@if(! empty($social['show']))
    <div class="rounded-xl bg-white/[0.04] border border-white/5 px-4 py-3 space-y-2" aria-live="polite">
        @if(! empty($social['live']))
            <p class="flex items-center gap-2 text-xs text-slate-300">
                <span class="relative flex h-2 w-2 shrink-0">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                <span><strong class="text-white">{{ number_format($social['live']) }} orang lain</strong> sedang melihat {{ $noun }} ini</span>
            </p>
        @endif

        @if(! empty($social['inquiries']))
            <p class="flex items-center gap-2 text-xs text-slate-300">
                <svg class="w-3.5 h-3.5 shrink-0 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                <span><strong class="text-white">{{ number_format($social['inquiries']) }} orang</strong> menanyakan {{ $noun }} ini dalam {{ $social['inquiry_hours'] }} jam terakhir</span>
            </p>
        @endif

        @if(! empty($social['views_today']))
            <p class="flex items-center gap-2 text-xs text-slate-300">
                <svg class="w-3.5 h-3.5 shrink-0 text-electric" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>Dilihat <strong class="text-white">{{ number_format($social['views_today']) }} orang</strong> hari ini</span>
            </p>
        @endif
    </div>
@endif