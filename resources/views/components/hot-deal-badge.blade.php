@props(['size' => 'sm'])
{{-- Label "Hot Deal". Pakai: <x-hot-deal-badge /> atau <x-hot-deal-badge size="lg" /> --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-md bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold uppercase tracking-wide shadow-lg shadow-red-900/30 ' . ($size === 'lg' ? 'px-3 py-1.5 text-xs' : 'px-2 py-0.5 sm:px-2.5 sm:py-1 text-[10px] sm:text-[11px]')]) }}>
    <svg class="{{ $size === 'lg' ? 'w-4 h-4' : 'w-3 h-3 sm:w-3.5 sm:h-3.5' }} shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152-.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z"/>
    </svg>
    Hot Deal
</span>