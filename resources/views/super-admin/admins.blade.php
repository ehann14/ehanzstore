@extends('layouts.admin')

@section('title', 'Manajemen Admin')

@section('content')
@php
    $superCount  = $admins->where('role', 'super_admin')->count();
    $adminCount  = $admins->where('role', 'admin')->count();
    $activeCount = $admins->where('is_active', true)->count();
@endphp

<div class="max-w-5xl mx-auto pb-16">

    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Manajemen Admin</h1>
        <p class="text-sm text-slate-400 mt-1">Daftar pengguna yang memiliki akses ke panel kontrol.</p>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ $superCount }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Super Admin</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ $adminCount }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Admin</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ $activeCount }}<span class="text-base text-slate-500">/{{ $admins->count() }}</span></p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Aktif</p>
        </div>
    </div>

    @if($admins->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center">
            <p class="text-slate-300">Belum ada admin terdaftar.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($admins as $admin)
                @php
                    $isSuper = $admin->role === 'super_admin';
                    $isMe    = $admin->id === auth()->id();
                @endphp
                <div class="glass-panel rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex items-center gap-4 min-w-0 flex-1">
                        <div class="w-12 h-12 shrink-0 rounded-full flex items-center justify-center text-white font-bold bg-gradient-to-br {{ $isSuper ? 'from-royal to-electric' : 'from-slate-600 to-slate-500' }}">
                            {{ strtoupper(mb_substr($admin->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-white truncate">{{ $admin->name }}</h3>
                                @if($isMe)
                                    <span class="px-2 py-0.5 rounded-md bg-white/10 text-slate-300 text-[10px] font-semibold uppercase tracking-wide">Anda</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-400 truncate">{{ $admin->email }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <span class="px-2.5 py-1 rounded-full border text-[11px] font-medium {{ $isSuper ? 'bg-electric/15 text-blue-300 border-electric/30' : 'bg-slate-500/15 text-slate-300 border-slate-500/30' }}">
                            {{ $isSuper ? 'Super Admin' : 'Admin' }}
                        </span>
                        <span class="px-2.5 py-1 rounded-full border text-[11px] font-medium {{ $admin->is_active ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' : 'bg-red-500/15 text-red-300 border-red-500/30' }}">
                            {{ $admin->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        @if($isSuper)
                            <span class="px-2.5 py-1 rounded-full border text-[11px] font-medium {{ $admin->hasTwoFactorEnabled() ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' : 'bg-amber-500/15 text-amber-300 border-amber-500/30' }}">
                                {{ $admin->hasTwoFactorEnabled() ? '2FA aktif' : '2FA belum aktif' }}
                            </span>
                        @endif
                        <span class="text-xs text-slate-500 sm:ml-2">Bergabung {{ $admin->created_at?->format('d M Y') ?? '-' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection