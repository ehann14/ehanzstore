@props(['size' => 'sm'])
{{--
    Badge "Titipan Terverifikasi": akun titipan yang lolos syarat utama titip akun
    (email bisa diganti ke Gmail pembeli, dan nomor proteksi bisa diganti ke pembeli bila proteksi ON).
    Pakai: <x-consignment-verified-badge /> atau <x-consignment-verified-badge size="lg" />
--}}
<span title="Akun titipan. Admin sudah memastikan email bisa diganti ke Gmail pembeli, dan nomor proteksi bisa diganti ke nomor pembeli bila proteksi ON."
      {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-emerald-500/10 border border-emerald-400/30 text-emerald-300 font-semibold whitespace-nowrap ' . ($size === 'lg' ? 'px-3 py-1.5 text-xs' : 'px-2 py-0.5 text-[10px] sm:text-[11px]')]) }}>
    <svg class="{{ $size === 'lg' ? 'w-4 h-4' : 'w-3 h-3 sm:w-3.5 sm:h-3.5' }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
    </svg>
    Titipan Terverifikasi
</span>