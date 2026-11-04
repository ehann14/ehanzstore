<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorEvent extends Model
{
    // Matikan fitur timestamps otomatis (created_at & updated_at)
    // Kita hanya akan menggunakan created_at secara manual
    public $timestamps = false;

    protected $fillable = [
        'anonymous_session_id',
        'event_type',
        'page_path',
        'coc_account_id',
        'event_metadata'
    ];

    protected $casts = [
        'event_metadata' => 'array',
    ];

    public function account()
    {
        // withTrashed: "Akun Paling Diminati" tetap menampilkan nama akun yang sudah dihapus
        return $this->belongsTo(CocAccount::class, 'coc_account_id')->withTrashed();
    }

    /**
     * Hapus event yang lebih tua dari $days hari, sedikit demi sedikit supaya tabel tidak terkunci lama.
     *
     * @param int|null $maxChunks batasi jumlah putaran (null = sampai habis)
     * @return int jumlah baris yang dihapus
     */
    public static function pruneOlderThan(int $days, int $chunk = 1000, ?int $maxChunks = null): int
    {
        if ($days <= 0) {
            return 0;
        }

        $cutoff  = now()->subDays($days);
        $chunk   = max(1, $chunk);
        $total   = 0;
        $rounds  = 0;

        do {
            $deleted = static::where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit($chunk)
                ->delete();

            $total += $deleted;
            $rounds++;
        } while ($deleted === $chunk && ($maxChunks === null || $rounds < $maxChunks));

        return $total;
    }
}