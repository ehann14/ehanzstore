@extends('layouts.admin')

@section('title', 'Titip Jual')

@section('content')
@php
    $rupiah  = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $isSuper = auth()->user()->isSuperAdmin();
    $tabs = [
        'unpaid' => ['Belum disetor', $summary['unpaid_count']],
        'paid'   => ['Sudah disetor', $summary['paid_count']],
        'stock'  => ['Stok titipan', $summary['stock_count']],
    ];
@endphp

<div class="max-w-6xl mx-auto pb-20">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white">Titip Jual</h1>
        <p class="text-sm text-slate-400 mt-1">
            Akun milik orang lain yang Anda jualkan: komisi toko dan setoran ke pemilik akun.
            Aktifkan lewat kotak <span class="text-slate-200">Mode titip jual</span> di form tambah / edit akun.
            @unless($isSuper) Hanya titipan milik Anda. @endunless
        </p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="glass-panel rounded-2xl p-4 sm:p-5 border {{ $summary['unpaid_total'] > 0 ? 'border-amber-500/30' : 'border-transparent' }}">
            <p class="text-lg sm:text-2xl font-display font-bold {{ $summary['unpaid_total'] > 0 ? 'text-amber-300' : 'text-white' }} truncate">{{ $rupiah($summary['unpaid_total']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Harus disetor ke pemilik</p>
            <p class="text-[11px] text-slate-600 mt-0.5">{{ $summary['unpaid_count'] }} transaksi</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-2xl font-display font-bold text-emerald-400 truncate">{{ $rupiah($summary['commission']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Komisi toko</p>
            <p class="text-[11px] text-slate-600 mt-0.5">dari {{ $summary['sold'] }} akun titipan terjual</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-2xl font-display font-bold text-white truncate">{{ $rupiah($summary['paid_total']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Sudah disetor</p>
            <p class="text-[11px] text-slate-600 mt-0.5">{{ $summary['paid_count'] }} transaksi</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ $summary['stock_count'] }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Stok titipan</p>
            <p class="text-[11px] text-slate-600 mt-0.5">nilai jual {{ $rupiah($summary['stock_value']) }}</p>
        </div>
    </div>

    {{-- Tab --}}
    <div class="flex gap-2 mb-4 overflow-x-auto">
        @foreach($tabs as $key => [$label, $n])
            <a href="{{ route('admin.consignments.index', ['tab' => $key]) }}"
               class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ $tab === $key ? 'bg-electric text-white' : 'bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 hover:text-white' }}">
                {{ $label }} ({{ $n }})
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.consignments.index') }}" class="flex gap-2 mb-6">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari pemilik, nomor WA, atau kode akun..."
               class="flex-1 min-w-0 bg-midnight border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric">
        <button type="submit" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-slate-200 transition">Cari</button>
        @if($search !== '')
            <a href="{{ route('admin.consignments.index', ['tab' => $tab]) }}" class="px-4 py-2.5 rounded-xl border border-white/10 text-sm text-slate-400 hover:text-white transition">Reset</a>
        @endif
    </form>

    {{-- Rekap per pemilik (tab Belum disetor) --}}
    @if($tab === 'unpaid' && $owners->isNotEmpty())
        <div class="glass-panel rounded-2xl border border-white/5 p-4 sm:p-5 mb-6">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Yang harus dibayar per pemilik</h2>
            <div class="divide-y divide-white/5">
                @foreach($owners as $owner)
                    @php
                        $ownerPhone = $owner->consignor_phone ? preg_replace('/^62/', '0', $owner->consignor_phone) : null;
                        $ownerWa    = $owner->consignor_phone
                            ? 'https://wa.me/' . $owner->consignor_phone . '?text=' . rawurlencode('Halo ' . $owner->consignor_name . ', ada ' . $owner->total . ' akun titipan Anda yang sudah terjual. Total bagian Anda ' . $rupiah($owner->amount) . '.')
                            : null;
                    @endphp
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white truncate">{{ $owner->consignor_name }}</p>
                            <p class="text-[11px] text-slate-500">
                                {{ $owner->total }} akun terjual
                                @if($ownerPhone) &middot; {{ $ownerPhone }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-sm font-bold text-amber-300 whitespace-nowrap">{{ $rupiah($owner->amount) }}</span>
                            @if($ownerWa)
                                <a href="{{ $ownerWa }}" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 text-[11px] font-semibold transition">WhatsApp</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($items->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center border border-white/5">
            @if($search !== '')
                <p class="text-slate-300 mb-1 font-medium">Tidak ada hasil untuk "{{ $search }}".</p>
                <p class="text-sm text-slate-500">Coba kata kunci lain.</p>
            @elseif($tab === 'unpaid')
                <p class="text-slate-200 mb-1 font-medium">Tidak ada setoran yang tertunda.</p>
                <p class="text-sm text-slate-500">Setiap akun titipan yang terjual akan muncul di sini sampai bagian pemilik Anda tandai lunas.</p>
            @elseif($tab === 'paid')
                <p class="text-slate-200 mb-1 font-medium">Belum ada setoran yang dilunasi.</p>
                <p class="text-sm text-slate-500">Riwayat setoran ke pemilik akan tampil di sini.</p>
            @else
                <p class="text-slate-200 mb-1 font-medium">Belum ada stok titipan.</p>
                <p class="text-sm text-slate-500">Centang "Mode titip jual" saat menambah atau mengedit akun.</p>
            @endif
        </div>
    @elseif($tab === 'stock')
        <div class="space-y-3">
            @foreach($items as $account)
                @php
                    $imageUrl = $account->primaryImage?->image_path ? thumb_url($account->primaryImage->image_path, 320) : null;
                    $commission = $account->commissionFor((float) $account->price);
                    $payout     = $account->payoutFor((float) $account->price);
                @endphp
                <div class="glass-panel rounded-2xl border border-white/5 p-3 sm:p-4 flex items-start gap-3 sm:gap-4">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 shrink-0 rounded-xl overflow-hidden bg-midnight border border-white/10">
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $account->name }}" loading="lazy" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-0.5">
                            <span class="text-[11px] font-mono text-slate-500">{{ $account->account_code }}</span>
                            <span class="px-2 py-0.5 rounded-md bg-electric/15 text-electric text-[11px] font-bold">{{ $account->badgeLabel() }}</span>
                            <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $account->publication_status === 'published' ? 'bg-electric/15 text-blue-300 border-electric/30' : 'bg-amber-500/15 text-amber-300 border-amber-500/30' }}">{{ $account->publication_status === 'published' ? 'Terbit' : 'Draft' }}</span>
                        </div>
                        <h3 class="font-semibold text-white truncate">{{ $account->name }}</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 mt-1">
                            Pemilik: <span class="text-slate-200">{{ $account->consignor_name }}</span>
                            @if($account->consignorLocalPhone()) &middot; {{ $account->consignorLocalPhone() }} @endif
                        </p>
                        <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">
                            Komisi {{ $account->commissionLabel() }} = <span class="text-emerald-400 font-semibold">{{ $rupiah($commission) }}</span>
                            &middot; setor ke pemilik <span class="text-slate-300 font-semibold">{{ $rupiah($payout) }}</span>
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-blue-400 font-bold whitespace-nowrap">{{ $rupiah($account->price) }}</p>
                        <a href="{{ route('admin.accounts.edit', $account) }}" class="inline-block mt-2 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-[11px] text-slate-200 transition">Edit</a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="space-y-3">
            @foreach($items as $sale)
                @php
                    $paid     = $sale->consignorIsPaid();
                    $account  = $sale->account;
                    $waText   = $paid
                        ? 'Halo ' . $sale->consignor_name . ', akun ' . $sale->account_code_snapshot . ' sudah terjual dan bagian Anda ' . $rupiah($sale->consignor_payout) . ' sudah kami setor. Terima kasih!'
                        : 'Halo ' . $sale->consignor_name . ', akun ' . $sale->account_code_snapshot . ' sudah terjual ' . $rupiah($sale->sale_price) . '. Bagian Anda ' . $rupiah($sale->consignor_payout) . ' (komisi toko ' . $rupiah($sale->commission_amount) . ').';
                    $waUrl    = $sale->consignorWhatsappUrl($waText);
                @endphp
                <div class="glass-panel rounded-2xl border {{ $paid ? 'border-white/5' : 'border-amber-500/20' }} p-4">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                <span class="text-[11px] font-mono text-slate-500">{{ $sale->transaction_code }}</span>
                                <span class="text-[11px] font-mono text-slate-500">{{ $sale->account_code_snapshot }}</span>
                                <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $paid ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' : 'bg-amber-500/15 text-amber-300 border-amber-500/30' }}">
                                    {{ $paid ? 'Sudah disetor' : 'Belum disetor' }}
                                </span>
                            </div>
                            <h3 class="font-semibold text-white truncate">{{ $account?->name ?? 'Akun dihapus' }}</h3>
                            <p class="text-[11px] sm:text-xs text-slate-400 mt-1">
                                Pemilik: <span class="text-slate-200">{{ $sale->consignor_name }}</span>
                                @if($sale->consignorLocalPhone()) &middot; {{ $sale->consignorLocalPhone() }} @endif
                            </p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Terjual {{ $sale->transactionTimeLabel() }} WIB
                                @if($sale->buyer_name) &middot; pembeli {{ $sale->buyer_name }} @endif
                                @if($paid) &middot; disetor {{ $sale->consignorPaidLabel() }} @endif
                            </p>
                        </div>

                        <div class="sm:text-right shrink-0">
                            <p class="text-[11px] text-slate-500">Harga jual {{ $rupiah($sale->sale_price) }}</p>
                            <p class="text-[11px] text-emerald-400">Komisi toko {{ $rupiah($sale->commission_amount) }}</p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-500 mt-1.5">Bagian pemilik</p>
                            <p class="text-lg font-bold {{ $paid ? 'text-slate-300' : 'text-amber-300' }} whitespace-nowrap">{{ $rupiah($sale->consignor_payout) }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-white/5">
                        @if($paid)
                            <form action="{{ route('admin.consignments.unpay', $sale) }}" method="POST"
                                  data-confirm="Batalkan tanda lunas untuk {{ $sale->account_code_snapshot }}? Setoran akan kembali ke daftar Belum disetor."
                                  onsubmit="return confirm(this.dataset.confirm)">
                                @csrf
                                <button type="submit" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-slate-300 transition">Batalkan tanda lunas</button>
                            </form>
                        @else
                            <form action="{{ route('admin.consignments.pay', $sale) }}" method="POST"
                                  data-confirm="Tandai bagian {{ $sale->consignor_name }} ({{ $rupiah($sale->consignor_payout) }}) sudah disetor? Pastikan uangnya sudah dikirim."
                                  onsubmit="return confirm(this.dataset.confirm)">
                                @csrf
                                <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition active:scale-95">Tandai sudah disetor</button>
                            </form>
                        @endif

                        @if($waUrl)
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 text-xs font-semibold transition">Kabari pemilik via WA</a>
                        @endif

                        <a href="{{ route('admin.sales.buyer', $sale) }}" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-slate-300 transition sm:ml-auto">Data pembeli</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($items->hasPages())
        <div class="mt-6">{{ $items->links() }}</div>
    @endif
</div>
@endsection