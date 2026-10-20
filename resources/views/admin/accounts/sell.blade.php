@extends('layouts.admin')

@php
    /** @var \App\Models\CocAccount $account */
    /** @var \App\Models\Sale|null $sale */
    $isEdit = isset($sale) && $sale;
    $imageUrl = $account->primaryImage?->image_path ? asset('storage/' . $account->primaryImage->image_path) : null;
@endphp

@section('title', $isEdit ? 'Data Pembeli' : 'Konfirmasi Penjualan')

@section('content')
<div class="max-w-3xl mx-auto pb-20">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.accounts.index') }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 transition text-slate-400 hover:text-white" aria-label="Kembali">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $isEdit ? 'Data Pembeli & Garansi' : 'Konfirmasi Penjualan' }}</h1>
            <p class="text-sm text-slate-400">
                {{ $isEdit
                    ? 'Lengkapi atau ubah data pembeli. Masa garansi dihitung ulang dari waktu serah terima.'
                    : 'Isi data pembeli untuk mencatat garansi ' . \App\Models\Sale::WARRANTY_DAYS . ' hari. Akun baru berstatus terjual setelah form ini disimpan.' }}
            </p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            <p class="font-semibold mb-1">Data belum bisa disimpan:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <!-- Ringkasan akun -->
    <div class="glass-panel rounded-2xl p-4 flex items-center gap-4 mb-6 border border-white/5">
        <div class="w-16 h-16 shrink-0 rounded-xl overflow-hidden bg-midnight border border-white/10">
            @if($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $account->name }}" class="w-full h-full object-cover">
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                <span class="text-[11px] font-mono text-slate-500">{{ $account->account_code }}</span>
                <span class="px-2 py-0.5 rounded-md bg-electric/15 text-electric text-[11px] font-bold">{{ $account->badgeLabel() }}</span>
                @if($isEdit)
                    <span class="text-[11px] font-mono text-slate-500">{{ $sale->transaction_code }}</span>
                @endif
            </div>
            <h2 class="font-semibold text-white truncate">{{ $account->name }}</h2>
            <p class="text-brand-400 font-bold text-sm">Harga listing: Rp {{ number_format($account->price, 0, ',', '.') }}</p>
        </div>
        @if($isEdit)
            @php [$badgeText, $badgeCls] = $sale->warrantyBadge(); @endphp
            <span class="hidden sm:inline-flex px-2.5 py-1 rounded-full border text-[11px] font-medium {{ $badgeCls }}">{{ $badgeText }}</span>
        @endif
    </div>

    <!-- Form -->
    <form action="{{ $isEdit ? route('admin.sales.buyer.update', $sale) : route('admin.accounts.sell.store', $account) }}"
          method="POST" class="glass-panel rounded-3xl p-6 md:p-8 space-y-8">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="space-y-4">
            <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider border-b border-white/5 pb-2">Data Pembeli &amp; Serah Terima</h3>

            @include('admin.sales._buyer-fields', [
                'sale'         => $isEdit ? $sale : null,
                'defaultPrice' => $account->price,
                'idPrefix'     => 'buyer',
            ])
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3 pt-4 border-t border-white/5">
            <a href="{{ route('admin.accounts.index') }}" class="text-center text-slate-400 hover:text-white text-sm font-medium transition px-4 py-3">Batal</a>
            <button type="submit" class="sm:ml-auto bg-brand-600 hover:bg-brand-500 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg shadow-brand-600/20 active:scale-95">
                {{ $isEdit ? 'Simpan Data Pembeli' : 'Konfirmasi Terjual' }}
            </button>
        </div>
    </form>
</div>
@endsection