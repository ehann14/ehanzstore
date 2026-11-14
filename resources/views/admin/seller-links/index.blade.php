@extends('layouts.admin')

@section('title', 'Link Titip Penjual')

@section('content')
<div class="max-w-4xl mx-auto pb-20">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.accounts.index') }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 transition text-slate-400 hover:text-white" title="Kembali ke Kelola Akun">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">Link Titip Penjual</h1>
            <p class="text-sm text-slate-400">Buat nama dulu, kirim link ke penjual. Penjual mengisi data &amp; foto sendiri, kamu tinggal meninjau dan menerbitkan.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    {{-- Buat link baru --}}
    <form action="{{ route('admin.seller-links.store') }}" method="POST" class="glass-panel rounded-2xl p-5 mb-8">
        @csrf
        <label for="sl_name" class="block text-xs font-semibold text-slate-400 mb-2">Nama (nama penjual atau label akun)</label>
        <div class="flex flex-col sm:flex-row gap-3">
            <input id="sl_name" type="text" name="name" value="{{ old('name') }}" required minlength="2" maxlength="100" placeholder="Contoh: Budi - TH14 Hero Max"
                   class="flex-1 bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:border-brand-500 outline-none transition">
            <button type="submit" class="bg-electric hover:bg-blue-500 text-white font-semibold px-6 py-3 rounded-xl text-sm transition shadow-lg shadow-electric/20">Buat Link</button>
        </div>
        <p class="text-[11px] text-slate-500 mt-2">Link berlaku {{ $days }} hari dan hanya bisa dipakai satu kali. Setelah penjual mengirim data, link otomatis mati.</p>
    </form>

    {{-- Sudah diisi penjual: siap ditinjau --}}
    <div class="mb-8">
        <h2 class="text-sm font-bold text-emerald-400 uppercase tracking-wider mb-3">Sudah diisi penjual ({{ $ready->count() }})</h2>
        @forelse($ready as $acc)
            <div class="glass-panel rounded-2xl p-4 mb-3 border border-emerald-500/20 flex items-start gap-4">
                <div class="w-16 h-16 rounded-xl overflow-hidden bg-midnight shrink-0 border border-white/10">
                    @if($acc->primaryImage)
                        <img src="{{ thumb_url($acc->primaryImage->image_path, 320) }}" alt="" class="w-full h-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-white truncate">{{ $acc->name }}</p>
                        <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium bg-emerald-500/15 text-emerald-300 border-emerald-500/30">Siap ditinjau</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ $acc->account_code }} &middot; TH {{ $acc->town_hall }} &middot; Rp {{ number_format((float) $acc->price, 0, ',', '.') }}
                        &middot; {{ $acc->images_count }} foto
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Penjual: {{ $acc->consignor_name }}
                        @if($acc->consignor_phone) &middot; {{ preg_replace('/^62/', '0', $acc->consignor_phone) }} @endif
                        @if($acc->submitted_at) &middot; dikirim {{ $acc->submitted_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB @endif
                    </p>
                </div>
                <a href="{{ route('admin.accounts.edit', $acc) }}"
                   class="shrink-0 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">Tinjau &amp; Terbitkan</a>
            </div>
        @empty
            <p class="text-sm text-slate-500 rounded-2xl border border-white/5 p-5">Belum ada data yang masuk dari penjual.</p>
        @endforelse
    </div>

    {{-- Menunggu diisi --}}
    <div>
        <h2 class="text-sm font-bold text-amber-400 uppercase tracking-wider mb-3">Menunggu diisi penjual ({{ $waiting->count() }})</h2>
        @forelse($waiting as $acc)
            @php
                $expired = $acc->sellerLinkIsExpired();
                $url     = $acc->sellerLinkUrl();
                $waText  = "Halo, ini link untuk mengisi data & screenshot akun yang mau dititip di EHANZSTORE.ID.\n"
                         . "Link hanya bisa dipakai 1 kali dan ditutup setelah kamu kirim:\n" . $url;
            @endphp
            <div class="glass-panel rounded-2xl p-4 mb-3 border {{ $expired ? 'border-red-500/20' : 'border-white/5' }}">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-semibold text-white">{{ $acc->name }}</p>
                    <span class="text-[11px] font-mono text-slate-500">{{ $acc->account_code }}</span>
                    @if($expired)
                        <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium bg-red-500/15 text-red-300 border-red-500/30">Link kedaluwarsa</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium bg-amber-500/15 text-amber-300 border-amber-500/30">Menunggu penjual</span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-500 mt-1">
                    @if($acc->submission_expires_at)
                        {{ $expired ? 'Berakhir' : 'Berlaku sampai' }} {{ $acc->submission_expires_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                    @endif
                </p>

                @if(! $expired && $url)
                    <div class="mt-3 flex flex-col sm:flex-row gap-2">
                        <input type="text" readonly value="{{ $url }}" onfocus="this.select()"
                               class="flex-1 min-w-0 bg-midnight/60 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-slate-300 font-mono">
                        <div class="flex gap-2">
                            <button type="button" data-copy="{{ $url }}"
                                    class="px-4 py-2.5 rounded-xl bg-electric hover:bg-blue-500 text-white text-xs font-bold transition whitespace-nowrap">Salin Link</button>
                            <a href="https://wa.me/?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener"
                               class="px-4 py-2.5 rounded-xl bg-green-600 hover:bg-green-500 text-white text-xs font-bold transition whitespace-nowrap">Kirim via WA</a>
                        </div>
                    </div>
                @endif

                <div class="mt-3 flex flex-wrap gap-2">
                    <form action="{{ route('admin.seller-links.renew', $acc) }}" method="POST"
                          onsubmit="return confirm('Buat link baru? Link yang lama langsung tidak bisa dipakai.')">
                        @csrf
                        <button class="px-3 py-1.5 rounded-lg border border-white/10 text-slate-300 hover:bg-white/5 text-xs font-medium transition">{{ $expired ? 'Aktifkan lagi (link baru)' : 'Buat ulang link' }}</button>
                    </form>
                    <form action="{{ route('admin.seller-links.destroy', $acc) }}" method="POST"
                          onsubmit="return confirm('Batalkan link ini? Draft kosongnya ikut dihapus.')">
                        @csrf @method('DELETE')
                        <button class="px-3 py-1.5 rounded-lg border border-red-500/30 text-red-300 hover:bg-red-500/10 text-xs font-medium transition">Batalkan</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500 rounded-2xl border border-white/5 p-5">Tidak ada link yang sedang menunggu.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const text = btn.dataset.copy;
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
                const t = document.createElement('textarea');
                t.value = text; document.body.appendChild(t); t.select();
                document.execCommand('copy'); t.remove();
            }
            const old = btn.textContent;
            btn.textContent = 'Tersalin ✓';
            setTimeout(function () { btn.textContent = old; }, 1800);
        });
    });
</script>
@endpush