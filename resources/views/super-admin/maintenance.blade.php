@extends('layouts.admin')

@section('title', 'Mode Maintenance')

@section('content')
@php
    $fieldClass = 'w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric';
    $isOn = old('active', $settings['active'] ? '1' : '0') == '1';
@endphp

<div class="max-w-3xl mx-auto pb-16">
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Mode Maintenance</h1>
        <p class="text-sm text-slate-400 mt-1">Saat aktif, pengunjung umum hanya melihat halaman maintenance. Admin yang sudah login tetap bisa membuka website seperti biasa.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="glass-panel rounded-3xl p-5 sm:p-6 mb-6 flex items-center gap-4 {{ $settings['active'] ? 'border-amber-500/30' : '' }}">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $settings['active'] ? 'bg-amber-500/15 text-amber-300' : 'bg-emerald-500/15 text-emerald-300' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div>
            <p class="font-display font-bold text-white text-lg">
                Status sekarang:
                <span class="{{ $settings['active'] ? 'text-amber-300' : 'text-emerald-300' }}">{{ $settings['active'] ? 'MAINTENANCE AKTIF' : 'Website normal' }}</span>
            </p>
            <p class="text-xs text-slate-400 mt-0.5">
                {{ $settings['active'] ? 'Pengunjung umum melihat halaman maintenance (HTTP 503).' : 'Semua pengunjung bisa membuka website.' }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('super-admin.maintenance.update') }}" class="glass-panel rounded-3xl p-6 md:p-8 space-y-6">
        @csrf

        <label class="flex items-start gap-4 p-4 rounded-2xl border border-white/10 bg-white/[0.03] cursor-pointer has-[:checked]:border-amber-500/40 has-[:checked]:bg-amber-500/5 transition">
            <input type="checkbox" name="active" value="1" @checked($isOn) class="mt-1 w-5 h-5 rounded accent-amber-500">
            <span>
                <span class="block font-semibold text-white">Aktifkan mode maintenance</span>
                <span class="block text-xs text-slate-400 mt-0.5">Centang lalu simpan untuk menutup website sementara. Hilangkan centang untuk membukanya lagi.</span>
            </span>
        </label>

        <div>
            <label for="m_title" class="block text-xs font-semibold text-slate-400 mb-2">Judul halaman</label>
            <input id="m_title" type="text" name="title" maxlength="80" value="{{ old('title', $settings['title']) }}" class="{{ $fieldClass }}">
        </div>

        <div>
            <label for="m_message" class="block text-xs font-semibold text-slate-400 mb-2">Pesan untuk pengunjung</label>
            <textarea id="m_message" name="message" rows="4" maxlength="500" class="{{ $fieldClass }}">{{ old('message', $settings['message']) }}</textarea>
        </div>

        <div>
            <label for="m_until" class="block text-xs font-semibold text-slate-400 mb-2">Perkiraan selesai <span class="text-slate-600 font-normal">(opsional, WIB)</span></label>
            <input id="m_until" type="datetime-local" name="until" value="{{ old('until', $untilInput) }}" class="{{ $fieldClass }}">
            <p class="text-[11px] text-slate-500 mt-1.5">Jika diisi, halaman maintenance menampilkan hitung mundur. Mode tidak mati otomatis saat waktunya lewat; matikan manual setelah selesai.</p>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3 pt-4 border-t border-white/5">
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="text-center text-sm text-slate-400 hover:text-white transition px-4 py-3">Lihat website</a>
            <button type="submit" class="sm:ml-auto bg-electric hover:bg-blue-500 text-white font-bold py-3 px-8 rounded-full transition shadow-lg shadow-electric/20 active:scale-95">
                Simpan Pengaturan
            </button>
        </div>
    </form>

    <div class="mt-6 rounded-2xl border border-white/5 bg-white/[0.02] p-5 text-xs text-slate-500 leading-relaxed">
        <p class="font-semibold text-slate-400 mb-1">Catatan</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>Halaman <span class="font-mono">/login</span> dan panel admin tetap bisa dibuka pengunjung agar kamu tidak terkunci dari sistem.</li>
            <li>Admin yang sedang login melihat website normal, jadi kamu bisa mengecek hasil perbaikan sebelum membukanya untuk umum.</li>
            <li>Kunjungan ke halaman maintenance tidak dihitung di analitik.</li>
        </ul>
    </div>
</div>
@endsection