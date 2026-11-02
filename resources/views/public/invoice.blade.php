@php
    $price    = (float) $sale->sale_price;
    $rp       = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $days     = $sale->warrantyDays();
    $expires  = $sale->warranty_expires_at;
    $handover = $sale->handover_at;

    $isActive  = $sale->warrantyIsActive();
    $isExpired = $expires && $handover && ! $isActive;

    if ($isActive) {
        $warrantyStatus = 'Aktif, ' . $sale->warrantyRemainingText();
    } elseif ($isExpired) {
        $warrantyStatus = 'Sudah berakhir';
    } else {
        $warrantyStatus = 'Belum dimulai';
    }

    $paid    = ($sale->payment_status ?? null) === 'paid';
    $waText  = rawurlencode("Halo Admin EHANZSTORE, saya mau tanya soal transaksi {$sale->transaction_code}.");
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice {{ $sale->transaction_code }}</title>
    @include('layouts.partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 24px 12px 48px;
            background: #e9ecf1; color: #1a1a1a;
            font-family: 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 14px; line-height: 1.5;
        }
        .wrap { max-width: 720px; margin: 0 auto; }
        .mono { font-family: Consolas, "Courier New", monospace; }

        /* Tombol di atas kertas */
        .actions { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 10px; }
        .actions a, .actions button {
            font: inherit; font-size: 13px; color: #1a1a1a; background: #fff;
            border: 1px solid #b9bfc9; border-radius: 4px; padding: 7px 14px;
            cursor: pointer; text-decoration: none;
        }
        .actions a:hover, .actions button:hover { background: #f4f5f7; }
        .actions .main { background: #1a1a1a; border-color: #1a1a1a; color: #fff; }
        .actions .main:hover { background: #333; }

        /* Kertas */
        .paper { background: #fff; border: 1px solid #cfd4dc; padding: 40px 44px; }

        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { width: 44px; height: 44px; object-fit: contain; }
        .brand b { display: block; font-family: 'Nunito', 'Inter', sans-serif; font-weight: 800; font-size: 17px; letter-spacing: .3px; }
        .brand span { font-size: 12.5px; color: #555; }
        .title { text-align: right; }
        .title h1 { margin: 0; font-family: 'Nunito', 'Inter', sans-serif; font-size: 26px; font-weight: 600; letter-spacing: 2px; color: #0645ad; }
        .title div { font-size: 13px; color: #555; margin-top: 2px; }

        hr { border: 0; border-top: 1px solid #1a1a1a; margin: 22px 0; }
        hr.thin { border-top: 1px solid #d9dce2; margin: 18px 0; }

        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }
        .meta h2, .section h2 {
            margin: 0 0 8px; font-size: 11.5px; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: #666;
        }
        dl { margin: 0; }
        dl > div { display: flex; gap: 12px; padding: 2px 0; }
        dt { width: 110px; flex-shrink: 0; color: #666; }
        dd { margin: 0; word-break: break-word; }

        .status {
            display: inline-block; border: 1px solid #1a1a1a; padding: 0 8px;
            font-size: 12px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 22px; }
        th {
            text-align: left; font-size: 11.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
            color: #666; padding: 8px 0; border-top: 1px solid #1a1a1a; border-bottom: 1px solid #1a1a1a;
        }
        td { padding: 12px 0; border-bottom: 1px solid #d9dce2; vertical-align: top; }
        .r { text-align: right; }
        .num { width: 36px; }
        td small { display: block; color: #666; font-size: 12.5px; margin-top: 1px; }

        .totals { margin-left: auto; width: 260px; margin-top: 10px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { border-top: 1px solid #1a1a1a; margin-top: 4px; padding-top: 8px; font-size: 16px; font-weight: 700; }

        .section { margin-top: 28px; }
        .warranty { width: 100%; margin-top: 0; }
        .warranty th, .warranty td { padding: 7px 10px 7px 0; border-left: 0; border-right: 0; }
        .warranty th { border-top: 1px solid #d9dce2; border-bottom: 1px solid #d9dce2; }
        .warranty td { border-bottom: 1px solid #d9dce2; }

        .notes { margin: 0; padding-left: 18px; color: #444; font-size: 13px; }
        .notes li { padding: 1px 0; }

        .foot { margin-top: 30px; font-size: 12px; color: #666; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }

        @media (max-width: 600px) {
            body { padding: 10px 6px 32px; }
            .paper { padding: 24px 18px; }
            .top { flex-direction: column; }
            .title { text-align: left; }
            .meta { grid-template-columns: 1fr; gap: 18px; }
            dt { width: 95px; }
            .totals { width: 100%; }
            .warranty thead { display: none; }
            .warranty, .warranty tbody, .warranty tr, .warranty td { display: block; width: 100%; }
            .warranty td { padding: 5px 0; border: 0; }
            .warranty td::before { content: attr(data-l) ": "; color: #666; }
            .warranty tr { border-top: 1px solid #d9dce2; border-bottom: 1px solid #d9dce2; padding: 6px 0; }
        }

        @page { size: A4; margin: 12mm; }
        @media print {
            body { background: #fff; padding: 0; font-size: 12.5px; }
            .actions { display: none !important; }
            .paper { border: 0; padding: 0; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr, .section { break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="wrap">

    <div class="actions">
        <button type="button" id="copyCode" data-code="{{ $sale->transaction_code }}">Salin kode</button>
        <a href="{{ $storeWaUrl }}?text={{ $waText }}" target="_blank" rel="noopener">Hubungi admin</a>
        <button type="button" class="main" onclick="window.print()">Unduh PDF</button>
    </div>

    <div class="paper">

        <div class="top">
            <div class="brand">
                <img src="{{ asset('images/logo.png') }}" alt="">
                <div>
                    <b>EHANZSTORE.ID</b>
                    <span>Jual beli akun Clash of Clans</span><br>
                    <span>WhatsApp {{ $storeWaDisplay }}</span>
                </div>
            </div>
            <div class="title">
                <h1>INVOICE</h1>
                <div class="mono">{{ $sale->transaction_code }}</div>
                <div>{{ $sale->transactionTimeLabel() }} WIB</div>
            </div>
        </div>

        <hr>

        <div class="meta">
            <div>
                <h2>Pembeli</h2>
                <dl>
                    <div><dt>Nama</dt><dd>{{ $sale->buyer_name ?? '-' }}</dd></div>
                    <div><dt>WhatsApp</dt><dd class="mono">{{ $phone }}</dd></div>
                </dl>
            </div>
            <div>
                <h2>Transaksi</h2>
                <dl>
                    <div><dt>Pembayaran</dt><dd>{{ $sale->payment_method ?? '-' }}</dd></div>
                    <div><dt>Serah terima</dt><dd>{{ $sale->handoverLabel() ?? 'Belum dikonfirmasi' }}</dd></div>
                    <div><dt>Status</dt><dd><span class="status">{{ $paid ? 'Lunas' : 'Selesai' }}</span></dd></div>
                </dl>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="num">No</th>
                    <th>Deskripsi</th>
                    <th class="r">Harga</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="num">1</td>
                    <td>
                        Akun Clash of Clans, {{ $account?->name ?? $sale->account_code_snapshot }}
                        <small>
                            Kode akun <span class="mono">{{ $sale->account_code_snapshot }}</span>
                            @if($account) &middot; {{ $account->badgeLabel() }} @endif
                            &middot; Garansi {{ $days }} hari
                        </small>
                    </td>
                    <td class="r">{{ $rp($price) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <div class="grand"><span>Total dibayar</span><span>{{ $rp($price) }}</span></div>
        </div>

        <div class="section">
            <h2>Garansi</h2>
            <table class="warranty">
                <thead>
                    <tr><th>Durasi</th><th>Mulai</th><th>Berakhir</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td data-l="Durasi">{{ $days }} hari ({{ $sale->warrantyTierShort() }})</td>
                        <td data-l="Mulai">{{ $sale->handoverLabel() ?? '-' }}</td>
                        <td data-l="Berakhir">{{ $sale->warrantyUntilLabel() ?? '-' }}</td>
                        <td data-l="Status">{{ $warrantyStatus }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Catatan</h2>
            <ol class="notes">
                <li>Segera ganti Gmail dan password akun setelah diterima.</li>
                <li>Periksa isi akun (hero, gems, bangunan) sesuai deskripsi saat serah terima.</li>
                <li>Untuk klaim garansi, kirim kode <span class="mono">{{ $sale->transaction_code }}</span> dan bukti masalah ke admin lewat WhatsApp sebelum garansi berakhir.</li>
                <li>Sisa garansi bisa dicek di {{ route('warranty.check') }}</li>
            </ol>
        </div>

        <hr class="thin">

        <div class="foot">
            <span>Terima kasih sudah berbelanja di EHANZSTORE.ID</span>
            <span>Dicetak {{ now(\App\Models\Sale::DISPLAY_TZ)->format('d/m/Y H:i') }} WIB</span>
        </div>
    </div>
</div>

<script>
    (function () {
        var btn = document.getElementById('copyCode');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var code = btn.dataset.code, old = btn.textContent;
            function done() { btn.textContent = 'Tersalin'; setTimeout(function () { btn.textContent = old; }, 1600); }
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(code).then(done);
            } else {
                var t = document.createElement('textarea');
                t.value = code; document.body.appendChild(t); t.select();
                document.execCommand('copy'); t.remove(); done();
            }
        });
    })();
</script>
</body>
</html>