<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\BandwidthSaver;
use App\Support\Housekeeping;
use App\Support\ImageOptimizer;
use App\Support\OrphanCleaner;
use Illuminate\Http\Request;

class BandwidthController extends Controller
{
    public function index()
    {
        $uploads = ImageOptimizer::scan();

        return view('super-admin.bandwidth', [
            'uploads'   => $uploads,
            'junk'      => Housekeeping::stats(),
            'pending'   => ImageOptimizer::pendingCount(),
            'gdOk'      => ImageOptimizer::available(),
            'webpOk'    => ImageOptimizer::supportsWebp(),
            'saverOn'   => BandwidthSaver::enabled(),
            'saverX'    => BandwidthSaver::FACTOR,
            'visitorDays' => Housekeeping::visitorDays(),
            'orphans'   => OrphanCleaner::scan(),
        ]);
    }

    /** Kecilkan gambar lama (banner + foto akun), per batch supaya tidak timeout. */
    public function optimize()
    {
        if (! ImageOptimizer::available()) {
            return back()->with('error', 'Ekstensi GD belum aktif di hosting, jadi gambar tidak bisa dikecilkan. Aktifkan "gd" di pengaturan PHP hosting.');
        }

        $before = ImageOptimizer::scan()['total'];
        $r      = ImageOptimizer::run();
        $after  = ImageOptimizer::scan()['total'];

        ActivityLog::record('optimize_images', null, 'Optimalkan gambar (hemat bandwidth)', [
            'processed' => $r['processed'],
            'saved'     => $r['saved'],
        ]);

        $msg = 'Selesai memproses ' . $r['processed'] . ' gambar. Folder uploads turun dari '
            . Housekeeping::bytes($before) . ' menjadi ' . Housekeeping::bytes($after)
            . ' (hemat ' . Housekeeping::bytes(max(0, $before - $after)) . ').';

        if ($r['remaining'] > 0) {
            $msg .= ' Masih ada ' . $r['remaining'] . ' gambar lagi, klik tombolnya sekali lagi.';
        }

        if ($r['errors'] > 0) {
            $msg .= ' ' . $r['errors'] . ' gambar gagal diproses dan dibiarkan seperti semula.';
        }

        return back()->with('success', $msg);
    }

    /** Bersihkan cache & data sampah. */
    public function clean(Request $request)
    {
        $data = $request->validate([
            'visitor_days' => ['nullable', 'integer', 'between:7,365'],
        ]);

        $result = Housekeeping::clean((int) ($data['visitor_days'] ?? Housekeeping::visitorDays()));

        ActivityLog::record('clean_cache', null, 'Bersihkan cache & data sampah', $result);

        $lines = [];
        foreach ($result as $label => $text) {
            $lines[] = $label . ': ' . $text;
        }

        return back()->with('success', 'Pembersihan selesai. ' . implode(' | ', $lines));
    }

    /** Hapus file di uploads/ yang tidak lagi dipakai database (sisa akun / banner yang dihapus). */
    public function orphans(Request $request)
    {
        $data = $request->validate([
            'paths'   => ['required', 'array', 'min:1', 'max:500'],
            'paths.*' => ['string', 'max:255'],
        ], [
            'paths.required' => 'Pilih minimal satu file untuk dihapus.',
            'paths.min'      => 'Pilih minimal satu file untuk dihapus.',
        ]);

        $r = OrphanCleaner::delete($data['paths']);

        if (! $r['ok']) {
            return back()->with('error', $r['error']);
        }

        ActivityLog::record('clean_orphan_uploads', null, 'Bersihkan file yatim di uploads', [
            'deleted' => $r['deleted'],
            'bytes'   => $r['bytes'],
            'skipped' => $r['skipped'],
            'failed'  => count($r['failed']),
        ]);

        $msg = $r['deleted'] . ' file yatim dihapus, ' . Housekeeping::bytes($r['bytes']) . ' dibebaskan.';

        if ($r['skipped'] > 0) {
            $msg .= ' ' . $r['skipped'] . ' file dilewati karena sudah dipakai lagi atau baru diunggah.';
        }

        if ($r['failed']) {
            return back()->with('error', $msg . ' ' . count($r['failed']) . ' file gagal dihapus, periksa izin tulis folder uploads.');
        }

        return back()->with('success', $msg);
    }

    /** Nyalakan / matikan Mode Hemat Bandwidth. */
    public function saver(Request $request)
    {
        $on = $request->boolean('on');

        BandwidthSaver::set($on);

        ActivityLog::record('toggle_bandwidth_saver', null, $on ? 'Mode hemat bandwidth dinyalakan' : 'Mode hemat bandwidth dimatikan');

        return back()->with('success', $on
            ? 'Mode Hemat Bandwidth AKTIF. Auto-update dashboard & lonceng notifikasi jadi ' . BandwidthSaver::FACTOR . 'x lebih jarang.'
            : 'Mode Hemat Bandwidth dimatikan. Auto-update kembali tiap 20 detik.');
    }
}