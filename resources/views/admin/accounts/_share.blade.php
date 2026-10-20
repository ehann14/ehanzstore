@php
    $shareUrl   = route('account.detail', $account->account_code);
    $isLive     = $account->publication_status === 'published' && $account->sale_status === 'available';
    $rupiahShare = 'Rp ' . number_format($account->price, 0, ',', '.');

    if ($account->isClan()) {
        $shareText = "🔥 CLAN CLASH OF CLANS DIJUAL 🔥\n"
            . "{$account->name} | Clan Lv. " . ($account->clan_level ?? 0) . "\n"
            . "Kode: {$account->account_code}\n"
            . "\n"
            . "🏰 Capital Hall Lv. " . ($account->capital_hall_level ?? 0)
            . " | 👥 Member " . ($account->clan_members ?? 0) . "/" . \App\Models\CocAccount::CLAN_MAX_MEMBERS . "\n"
            . "🏆 Liga: " . ($account->clan_league ?: 'Unranked') . "\n"
            . "\n"
            . "💰 Harga: {$rupiahShare}\n"
            . "🛒 Detail & foto: {$shareUrl}\n"
            . "\n"
            . "Bergaransi 7 hari - EHANZSTORE.ID";
    } else {
        $shareText = "🔥 AKUN CLASH OF CLANS DIJUAL 🔥\n"
            . "{$account->name} | TH {$account->town_hall}\n"
            . "Kode: {$account->account_code}\n"
            . "\n"
            . "👑 BK Lv. " . ($account->barbarian_king_level ?? 0)
            . " | AQ Lv. " . ($account->archer_queen_level ?? 0)
            . " | MP Lv. " . ($account->minion_prince_level ?? 0) . "\n"
            . "🛡️ GW Lv. " . ($account->grand_warden_level ?? 0)
            . " | RC Lv. " . ($account->royal_champion_level ?? 0)
            . " | DD Lv. " . ($account->dragon_duke_level ?? 0) . "\n"
            . "🔒 Proteksi: " . ($account->protection_status === 'Protected' ? 'ON' : 'OFF') . "\n"
            . "📧 Email: " . ($account->email_change_status === 'Changeable' ? 'Bisa Ganti' : 'Tidak Bisa Ganti') . "\n"
            . "\n"
            . "💰 Harga: {$rupiahShare}\n"
            . "🛒 Detail & foto: {$shareUrl}\n"
            . "\n"
            . "Bergaransi 7 hari - EHANZSTORE.ID";
    }
@endphp

<div class="glass-panel rounded-3xl p-6 md:p-8 mb-6" id="sharePanel">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider">Bagikan Akun</h3>
            <p class="text-xs text-slate-500 mt-1">Bagikan link atau teks promosi akun ini ke calon pembeli.</p>
        </div>
        @if($isLive)
            <span class="shrink-0 px-2.5 py-1 rounded-full border text-[11px] font-medium bg-emerald-500/15 text-emerald-300 border-emerald-500/30">Tayang di katalog</span>
        @else
            <span class="shrink-0 px-2.5 py-1 rounded-full border text-[11px] font-medium bg-amber-500/15 text-amber-300 border-amber-500/30">Belum tayang</span>
        @endif
    </div>

    @unless($isLive)
        <div class="mb-4 p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-200 text-xs leading-relaxed">
            Link ini baru bisa dibuka pembeli jika Status Terbit = <strong>Terbit</strong> dan Status Penjualan = <strong>Tersedia</strong>
            (status tersimpan saat ini: {{ $account->publication_status === 'published' ? 'Terbit' : 'Draft' }} /
            {{ ['available' => 'Tersedia', 'sold' => 'Terjual', 'archived' => 'Diarsipkan'][$account->sale_status] ?? $account->sale_status }}).
        </div>
    @endunless

    <!-- Link akun -->
    <label class="block text-xs font-semibold text-slate-400 mb-2">Link Akun</label>
    <div class="flex flex-col sm:flex-row gap-2 mb-5">
        <input type="text" id="shareUrlInput" value="{{ $shareUrl }}" readonly onclick="this.select()"
               class="flex-1 min-w-0 bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-slate-200 font-mono outline-none">
        <button type="button" onclick="adminCopy(document.getElementById('shareUrlInput').value, this, 'Salin Link')"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-semibold text-white transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span>Salin Link</span>
        </button>
    </div>

    <!-- Teks promosi -->
    <label class="block text-xs font-semibold text-slate-400 mb-2">Teks Promosi</label>
    <textarea id="shareTextInput" rows="11" readonly
              class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none resize-none mb-4">{{ $shareText }}</textarea>

    <div class="flex flex-wrap gap-2">
        <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-green-600 hover:bg-green-500 text-white text-sm font-semibold transition">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
            Kirim via WhatsApp
        </a>
        <a href="https://t.me/share/url?url={{ rawurlencode($shareUrl) }}&text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-sm font-semibold transition">
            Telegram
        </a>
        <button type="button" onclick="adminCopy(document.getElementById('shareTextInput').value, this, 'Salin Teks')"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white text-sm font-semibold transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span>Salin Teks</span>
        </button>
        <button type="button" id="adminNativeShare" class="hidden inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-semibold transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            Bagikan Lainnya
        </button>
    </div>
</div>

@push('scripts')
<script>
    function fallbackCopy(text, done) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) { alert('Gagal menyalin, salin manual dari kolom.'); }
        document.body.removeChild(ta);
    }

    function adminCopy(text, btn, defaultLabel) {
        const label = btn.querySelector('span');
        const done = () => {
            if (!label) return;
            label.textContent = 'Tersalin!';
            setTimeout(() => { label.textContent = defaultLabel; }, 1800);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
        } else {
            fallbackCopy(text, done);
        }
    }

    (function () {
        const btn = document.getElementById('adminNativeShare');
        if (!btn || !navigator.share) return;
        btn.classList.remove('hidden');
        btn.addEventListener('click', function () {
            navigator.share({
                title: @json($account->name . ' - EHANZSTORE.ID'),
                text: document.getElementById('shareTextInput').value,
                url: document.getElementById('shareUrlInput').value
            }).catch(() => {});
        });
    })();
</script>
@endpush