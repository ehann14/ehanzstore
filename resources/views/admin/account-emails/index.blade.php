@extends('layouts.admin')

@section('title', 'Email Akun')

@section('content')
<div class="max-w-4xl mx-auto pb-20">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.accounts.index') }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 transition text-slate-400 hover:text-white" title="Kembali ke Kelola Akun">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">Email Akun</h1>
            <p class="text-sm text-slate-400">Daftar Gmail yang dipakai di akun. <span class="text-amber-300">&#128274; Hanya admin yang melihat, tidak tampil ke pembeli.</span></p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="glass-panel rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold text-white">{{ $total }}</p>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">Total email</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold text-emerald-400">{{ $free }}</p>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">Tersedia</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold text-amber-400">{{ $used }}</p>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">Dipakai akun</p>
        </div>
    </div>

    {{-- Tambah email --}}
    <form action="{{ route('admin.account-emails.store') }}" method="POST" class="glass-panel rounded-2xl p-5 mb-8">
        @csrf
        <label for="ae_emails" class="block text-xs font-semibold text-slate-400 mb-2">Tambah email</label>
        <textarea id="ae_emails" name="emails" rows="3" required placeholder="ferhan142009@gmail.com&#10;akunbaru@gmail.com | email cadangan"
                  class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:border-brand-500 outline-none transition resize-y font-mono text-sm">{{ old('emails') }}</textarea>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-3">
            <p class="text-[11px] text-slate-500">Satu baris satu email. Catatan boleh ditambah setelah tanda <span class="font-mono text-slate-300">|</span>. Email yang sama (termasuk versi bertitik / <span class="font-mono text-slate-300">+tag</span> milik Gmail yang sama) otomatis dilewati.</p>
            <button type="submit" class="shrink-0 bg-electric hover:bg-blue-500 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition shadow-lg shadow-electric/20">Simpan Email</button>
        </div>
    </form>

    {{-- Cari & filter --}}
    @php
        $tabs = ['all' => 'Semua', 'free' => 'Tersedia', 'used' => 'Dipakai'];
    @endphp
    <div class="flex flex-col sm:flex-row gap-3 sm:items-center justify-between mb-4">
        <div class="flex gap-2">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.account-emails.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $search])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === $key ? 'bg-electric text-white' : 'bg-white/5 text-slate-400 hover:text-white hover:bg-white/10' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.account-emails.index') }}" class="flex gap-2">
            @if($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari email / catatan"
                   class="bg-brand-950/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-brand-500 outline-none transition w-full sm:w-56">
            <button type="submit" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 text-xs font-semibold transition">Cari</button>
        </form>
    </div>

    {{-- Daftar --}}
    <div class="space-y-3">
        @forelse($emails as $item)
            @php $holder = $item->account; @endphp
            <div class="glass-panel rounded-2xl p-4 border {{ $holder ? 'border-amber-500/20' : 'border-white/5' }}">
                <div class="flex flex-wrap items-start gap-3 justify-between">
                    <div class="min-w-0">
                        <p class="font-mono text-sm text-white break-all" data-email-text>{{ $item->email }}</p>
                        @if($item->note)<p class="text-xs text-slate-400 mt-0.5">{{ $item->note }}</p>@endif
                        <div class="mt-2">
                            @if($holder)
                                <a href="{{ route('admin.accounts.edit', $holder) }}"
                                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] font-medium bg-amber-500/15 text-amber-300 border-amber-500/30 hover:bg-amber-500/25 transition">
                                    Dipakai {{ $holder->account_code }} &middot; {{ \Illuminate\Support\Str::limit($holder->name, 28) }}
                                </a>
                            @else
                                <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium bg-emerald-500/15 text-emerald-300 border-emerald-500/30">Tersedia</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" data-copy="{{ $item->email }}"
                                class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-slate-300 transition">Salin</button>
                        <button type="button" data-edit-toggle="{{ $item->id }}"
                                class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-slate-300 transition">Ubah</button>
                        @if($holder)
                            <button type="button" disabled title="Sedang dipakai akun, tidak bisa dihapus"
                                    class="px-3 py-1.5 rounded-lg bg-red-600/40 text-white/60 text-xs font-bold cursor-not-allowed">Hapus</button>
                        @else
                            <form action="{{ route('admin.account-emails.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus email ini dari daftar?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Form ubah (tersembunyi) --}}
                <form id="edit-{{ $item->id }}" action="{{ route('admin.account-emails.update', $item) }}" method="POST" class="hidden mt-3 pt-3 border-t border-white/5">
                    @csrf @method('PUT')
                    <div class="grid sm:grid-cols-2 gap-3">
                        <input type="email" name="email" value="{{ $item->email }}" required maxlength="191"
                               class="bg-brand-950/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-brand-500 outline-none transition">
                        <input type="text" name="note" value="{{ $item->note }}" maxlength="255" placeholder="Catatan (opsional)"
                               class="bg-brand-950/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-brand-500 outline-none transition">
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-electric hover:bg-blue-500 text-white text-xs font-bold transition">Simpan</button>
                        <button type="button" data-edit-toggle="{{ $item->id }}" class="px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 text-xs font-semibold transition">Batal</button>
                    </div>
                </form>
            </div>
        @empty
            <p class="text-sm text-slate-500 rounded-2xl border border-white/5 p-6 text-center">
                @if($search !== '' || $status !== 'all')
                    Tidak ada email yang cocok.
                @else
                    Belum ada email terdaftar. Tambahkan di kotak atas.
                @endif
            </p>
        @endforelse
    </div>

    @if($emails->hasPages())
        <div class="mt-6">{{ $emails->links() }}</div>
    @endif

    <p class="text-[11px] text-slate-500 mt-6">
        Email yang dipakai suatu akun tidak bisa dipilih untuk akun lain. Begitu akunnya <span class="text-slate-300">terjual</span> (atau dihapus),
        email otomatis dilepas dari data akun dan kembali berstatus <span class="text-emerald-300">Tersedia</span>.
    </p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-edit-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = document.getElementById('edit-' + btn.dataset.editToggle);
            if (form) form.classList.toggle('hidden');
        });
    });

    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(btn.dataset.copy).then(function () {
                var t = btn.textContent;
                btn.textContent = 'Tersalin';
                setTimeout(function () { btn.textContent = t; }, 1200);
            });
        });
    });
})();
</script>
@endpush