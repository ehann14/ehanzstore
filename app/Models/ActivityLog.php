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
        try {
            $userId = Auth::id();

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