@extends('layouts.admin')

@section('title', 'Pembayaran (QRIS)')

@section('content')
@php
    $fieldClass = 'w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric';
    $has = $qris['image_url'] !== '';
@endphp

<div class="max-w-3xl mx-auto pb-16">
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Pembayaran (QRIS)</h1>
        <p class="text-sm text-slate-400 mt-1">Gambar QRIS ini tampil di halaman booking pembeli, tepat di bawah nominal DP. Pembeli bisa langsung membayar tanpa menunggu info dari admin.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            <ul class="list-disc pl-5 space-y-0.5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('super-admin.payment.update') }}" enctype="multipart/form-data" class="glass-panel rounded-3xl p-6 md:p-8 space-y-6">
        @csrf

        <div>
            <label for="p_qris" class="block text-xs font-semibold text-slate-400 mb-2">Gambar QRIS</label>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 space-y-4">
                <div class="flex items-center justify-center rounded-xl bg-white min-h-[180px] p-3">
                    <img id="p_preview" src="{{ $qris['image_url'] }}" alt="QRIS saat ini" class="{{ $has ? '' : 'hidden' }} max-h-72 w-auto max-w-full object-contain">
                    <p id="p_empty" class="{{ $has ? 'hidden' : '' }} text-xs text-slate-500 text-center px-4">Belum ada QRIS. Pembeli hanya melihat tombol "Minta info pembayaran".</p>
                </div>

                <input id="p_qris" type="file" name="qris" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-slate-300 file:mr-4 file:py-2.5 file:px-5 file:rounded-full file:border-0 file:bg-electric file:text-white file:font-semibold file:cursor-pointer hover:file:bg-blue-500">
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    JPG, PNG, atau WebP, maksimal 3 MB. Pakai gambar QRIS yang tajam dan tidak terpotong (bagian putih di tepi QR jangan dibuang) supaya mudah dipindai.
                </p>

                @if($has)
                    <label class="flex items-center gap-3 text-sm text-slate-300 cursor-pointer">
                        <input type="checkbox" name="remove_qris" value="1" class="w-4 h-4 rounded accent-red-500">
                        Hapus QRIS (pembeli tidak lagi melihat gambar ini)
                    </label>
                @endif
            </div>
        </div>

        <div>
            <label for="p_note" class="block text-xs font-semibold text-slate-400 mb-2">Catatan di bawah QRIS <span class="text-slate-600 font-normal">(opsional)</span></label>
            <textarea id="p_note" name="note" rows="3" maxlength="300" placeholder="Mis. A.n. EHANZSTORE. Rekening BCA 1234567890 a.n. ... (bila bayar lewat transfer bank)"
                      class="{{ $fieldClass }}">{{ old('note', $qris['note']) }}</textarea>
            <p class="text-[11px] text-slate-500 mt-1.5">Cocok untuk nama merchant atau rekening bank cadangan.</p>
        </div>

        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-200/90 leading-relaxed">
            <b class="text-amber-100">Penting:</b> QRIS toko biasanya statis (tanpa nominal), jadi pembeli mengetik nominalnya sendiri. Halaman booking sudah menampilkan nominal DP besar dengan tombol salin. Cocokkan nominal yang masuk dengan kode booking sebelum mengonfirmasi DP.
        </div>

        <div class="flex justify-end pt-4 border-t border-white/5">
            <button type="submit" class="bg-electric hover:bg-blue-500 text-white font-bold py-3 px-8 rounded-full transition shadow-lg shadow-electric/20 active:scale-95">Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('p_qris'), img = document.getElementById('p_preview'), empty = document.getElementById('p_empty');
        if (!input) return;
        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            if (!f || !/^image\//.test(f.type)) return;
            img.src = URL.createObjectURL(f);
            img.classList.remove('hidden');
            empty.classList.add('hidden');
        });
    })();
</script>
@endpush