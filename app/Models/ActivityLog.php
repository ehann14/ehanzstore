<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Daftar aksi yang dicatat: label tampilan dan warna lencana.
     * Warna: slate, green, blue, amber, red.
     */
    public const ACTIONS = [
        'login'          => ['label' => 'Login',              'tone' => 'slate'],
        'logout'         => ['label' => 'Logout',             'tone' => 'slate'],
        'create_account' => ['label' => 'Tambah akun',        'tone' => 'green'],
        'update_account' => ['label' => 'Ubah akun',          'tone' => 'blue'],
        'update_status'  => ['label' => 'Ubah status akun',   'tone' => 'blue'],
        'delete_image'   => ['label' => 'Hapus foto akun',    'tone' => 'red'],
        'delete_account' => ['label' => 'Hapus akun',         'tone' => 'red'],
        'create_banner'  => ['label' => 'Tambah banner',      'tone' => 'green'],
        'update_banner'  => ['label' => 'Ubah banner',        'tone' => 'blue'],
        'toggle_banner'  => ['label' => 'Tampil/sembunyi banner', 'tone' => 'amber'],
        'delete_banner'  => ['label' => 'Hapus banner',       'tone' => 'red'],
        'confirm_sale'   => ['label' => 'Konfirmasi penjualan', 'tone' => 'green'],
        'sell_account'   => ['label' => 'Jual akun + data pembeli', 'tone' => 'green'],
        'update_buyer'   => ['label' => 'Ubah data pembeli',  'tone' => 'blue'],
        'approve_review' => ['label' => 'Setujui ulasan',     'tone' => 'green'],
        'reject_review'  => ['label' => 'Tolak ulasan',       'tone' => 'amber'],
        'delete_review'  => ['label' => 'Hapus ulasan',       'tone' => 'red'],
        'apply_price_suggestion' => ['label' => 'Terapkan saran harga', 'tone' => 'blue'],
        // Keamanan login
        'login_failed'   => ['label' => 'Login gagal',        'tone' => 'amber'],
        'login_locked'   => ['label' => 'Login diblokir sementara', 'tone' => 'red'],
        'two_factor_failed'    => ['label' => '2FA: kode salah',         'tone' => 'amber'],
        'two_factor_enabled'   => ['label' => '2FA diaktifkan',          'tone' => 'green'],
        'two_factor_disabled'  => ['label' => '2FA dinonaktifkan',       'tone' => 'red'],
        'two_factor_recovery_used'  => ['label' => '2FA: kode pemulihan dipakai', 'tone' => 'amber'],
        'two_factor_recovery_reset' => ['label' => '2FA: kode pemulihan dibuat ulang', 'tone' => 'blue'],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat aktivitas pengguna yang sedang login.
     * Kegagalan mencatat tidak boleh mengganggu proses utama, jadi error hanya dilaporkan.
     */
    public static function record(string $action, ?Model $entity = null, ?string $label = null, array $extra = []): void
    {
        static::recordFor(Auth::id(), $action, $entity, $label, $extra);
    }

    /**
     * Catat aktivitas untuk pengguna tertentu, termasuk yang belum/tidak sedang login
     * (mis. percobaan login gagal pada akun yang ada).
     */
    public static function recordFor(?int $userId, string $action, ?Model $entity = null, ?string $label = null, array $extra = []): void
    {
        try {
            if (! $userId) {
                return;
            }

            static::create([
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => $entity ? $entity::class : null,
                'entity_id'   => $entity?->getKey(),
                'metadata'    => array_merge([
                    'label' => $label,
                    'ip'    => request()->ip(),
                ], $extra),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}