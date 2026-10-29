@props(['note' => true, 'compact' => false])
{{--
    Badge jam operasional (Buka / Tutup, WIB 24 jam). Pakai: <x-store-hours-badge /> atau
    <x-store-hours-badge :compact="true" /> (keterangan jam disembunyikan di layar sangat kecil).
    Status ikut berubah sendiri saat jam buka/tutup terlewati, lewat skrip kecil di layouts/app.blade.php.
--}}
@php $s = \App\Support\StoreHours::status(); @endphp
<span data-store-hours
      data-open="{{ $s['open'] ? '1' : '0' }}"
      data-open-min="{{ $s['open_min'] }}"
      data-close-min="{{ $s['close_min'] }}"
      data-note-open="{{ $s['note_open'] }}"
      data-note-closed="{{ $s['note_closed'] }}"
      title="Jam operasional {{ $s['range'] }}"
      {{ $attributes->merge(['class' => 'sh-badge inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold whitespace-nowrap']) }}>
    <span class="sh-dot" aria-hidden="true"></span>
    <span data-sh-label>{{ $s['label'] }}</span>
    @if($note)
        <span data-sh-note class="font-medium opacity-80 {{ $compact ? 'hidden sm:inline' : '' }}">&middot; {{ $s['note'] }}</span>
    @endif
</span>