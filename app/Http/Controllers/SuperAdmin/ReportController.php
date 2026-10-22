<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\SalesExporter;
use App\Support\SalesReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private const MIME = [
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pdf'  => 'application/pdf',
    ];

    /**
     * Halaman laporan bulanan.
     */
    
public function monthly(Request $request)
{
    $request->validate([
        'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
    ]);

    $report = SalesReport::monthly($request->query('month'));

    $summary = $report['summary'] ?? [];

    $stats = [
        'revenue'  => (float) ($summary['revenue'] ?? 0),
        'cost'     => (float) ($summary['cost'] ?? 0),
        'profit'   => (float) ($summary['profit'] ?? 0),
        'sold'     => (int) ($summary['sold'] ?? 0),
        'accounts' => (int) ($summary['accounts'] ?? 0),
        'clans'    => (int) ($summary['clans'] ?? 0),
        'completed' => (int) ($summary['sold'] ?? 0),
        'pending'  => 0,
    ];

    // Data transaksi untuk tabel laporan bulanan.
    $sales = $report['transactions'] ?? collect();

    // Data transaksi pending untuk periode laporan.
    $range = $report['range'] ?? [];

    if (!empty($range['from']) && !empty($range['to'])) {
        $stats['pending'] = \Illuminate\Support\Facades\DB::table('sales')
            ->where('transaction_status', 'pending')
            ->whereBetween(
                \Illuminate\Support\Facades\DB::raw(
                    'COALESCE(sales.sold_at, sales.created_at)'
                ),
                [$range['from'], $range['to']]
            )
            ->count();
    }

    return view('super-admin.report', [
        'report' => $report,
        'stats'  => $stats,
        'sales'  => $sales,
    ]);
}


    /**
     * Ekspor laporan bulanan ke Excel atau PDF.
     */
    public function exportMonthly(Request $request)
    {
        $request->validate([
            'month' => [
                'nullable',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
            ],
            'format' => [
                'required',
                'in:xlsx,pdf',
            ],
        ]);

        $report = SalesReport::monthly($request->query('month'));
        $format = $request->query('format');

        $content = $format === 'pdf'
            ? SalesExporter::monthlyPdf($report)
            : SalesExporter::monthlyXlsx($report);

        return $this->download(
            $content,
            'laporan-bulanan-' . $report['month'],
            $format
        );
    }

    /**
     * Ekspor transaksi berdasarkan tanggal dan status.
     */
    public function exportSales(Request $request)
    {
        $filters = $request->validate([
            'format' => [
                'required',
                'in:xlsx,pdf',
            ],
            'from' => [
                'nullable',
                'date',
            ],
            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],
            'status' => [
                'nullable',
                'in:all,completed,pending,cancelled',
            ],
        ], [
            'to.after_or_equal' =>
                'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $filters['status'] = $filters['status'] ?? 'completed';

        $transactions = SalesReport::transactions($filters);
        $rows = SalesReport::rows($transactions);
        $subtitle = $this->subtitle($filters);

        $content = $filters['format'] === 'pdf'
            ? SalesExporter::transactionsPdf($rows, $subtitle)
            : SalesExporter::transactionsXlsx($rows, $subtitle);

        return $this->download(
            $content,
            'transaksi-' . now(SalesReport::TZ)->format('Ymd-His'),
            $filters['format']
        );
    }

    /**
     * Keterangan periode dan status pada file ekspor.
     */
    private function subtitle(array $filters): string
    {
        $formatDate = fn (string $date) =>
            \Illuminate\Support\Carbon::parse($date)
                ->locale('id')
                ->translatedFormat('d M Y');

        $period = match (true) {
            !empty($filters['from']) && !empty($filters['to']) =>
                $formatDate($filters['from']) . ' - ' .
                $formatDate($filters['to']),

            !empty($filters['from']) =>
                'sejak ' . $formatDate($filters['from']),

            !empty($filters['to']) =>
                'sampai ' . $formatDate($filters['to']),

            default => 'Semua waktu',
        };

        $status = $filters['status'] === 'all'
            ? 'Semua status'
            : (
                SalesReport::STATUS_LABELS[$filters['status']]
                ?? $filters['status']
            );

        return 'Periode: ' . $period . ' | Status: ' . $status;
    }

    /**
     * Mengirim file unduhan.
     */
    private function download(
        string $content,
        string $name,
        string $format
    ): Response {
        return response($content, 200, [
            'Content-Type' => self::MIME[$format],
            'Content-Disposition' =>
                'attachment; filename="' . $name . '.' . $format . '"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}