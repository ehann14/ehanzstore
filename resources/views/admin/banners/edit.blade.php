@extends('layouts.admin')

@section('title', 'Edit Banner')

@section('content')
<div class="max-w-3xl mx-auto pb-20">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.banners.index') }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 transition text-slate-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">Edit Banner</h1>
            <p class="text-sm text-slate-400">{{ $banner->title }}</p>
        </div>
    </div>

    <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data" class="glass-panel rounded-3xl p-6 md:p-8 space-y-8">
        @csrf
        @method('PUT')
        @include('admin.banners._form', ['banner' => $banner])

        <div class="flex gap-3 pt-2">
            <a href="{{ route('admin.banners.index') }}" class="px-6 py-3 rounded-xl border border-white/10 text-slate-300 hover:bg-white/5 transition text-sm">Batal</a>
            <button type="submit" class="flex-1 py-3 rounded-xl bg-electric hover:bg-blue-500 text-white font-semibold transition text-sm">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection