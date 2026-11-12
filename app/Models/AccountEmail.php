<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Email (Gmail) milik toko yang dipakai di akun game. HANYA untuk admin: tidak pernah ditampilkan ke pembeli.
 * Satu email hanya bisa dipakai satu akun pada satu waktu. Akun yang terjual / dihapus melepas email-nya
 * (lihat CocAccount::booted), sehingga email itu bisa dipakai akun lain.
 */
class AccountEmail extends Model
{
    protected $fillable = ['email', 'note', 'created_by'];

    /** Akun aktif yang sedang memakai email ini (null = email tersedia). */
    public function account()
    {
        return $this->hasOne(CocAccount::class, 'account_email_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Rapikan input: buang spasi, huruf kecil. */
    public static function clean(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    /**
     * Kunci pembanding duplikat. Gmail mengabaikan titik dan bagian setelah "+",
     * jadi "ferhan.142009+toko@gmail.com" dianggap sama dengan "ferhan142009@gmail.com".
     */
    public static function duplicateKey(string $email): string
    {
        $email = static::clean($email);

        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local  = explode('+', $local, 2)[0];
            $local  = str_replace('.', '', $local);
            $domain = 'gmail.com';
        }

        return $local . '@' . $domain;
    }

    /** Admin biasa hanya melihat email yang ia daftarkan sendiri; super admin melihat semua. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $user->isSuperAdmin() ? $query : $query->where('created_by', $user->id);
    }

    /** Email yang belum dipakai akun mana pun. $exceptAccountId: abaikan pemakaian oleh akun itu (saat edit akun yang sama). */
    public function scopeFree(Builder $query, ?int $exceptAccountId = null): Builder
    {
        return $query->whereNotIn('account_emails.id', function ($sub) use ($exceptAccountId) {
            $sub->select('account_email_id')
                ->from('coc_accounts')
                ->whereNotNull('account_email_id')
                ->when($exceptAccountId, fn ($q) => $q->where('id', '!=', $exceptAccountId));
        });
    }

    /** Email yang sedang dipakai suatu akun. */
    public function scopeUsed(Builder $query): Builder
    {
        return $query->whereIn('account_emails.id', function ($sub) {
            $sub->select('account_email_id')->from('coc_accounts')->whereNotNull('account_email_id');
        });
    }

    /** Akun yang memegang email ini (termasuk yang soft delete, sesuai unique index di database). */
    public function holder(?int $exceptAccountId = null): ?CocAccount
    {
        return CocAccount::withTrashed()
            ->where('account_email_id', $this->id)
            ->when($exceptAccountId, fn ($q) => $q->where('id', '!=', $exceptAccountId))
            ->first();
    }

    /** Lepas email dari semua akun (dipakai sebelum email dihapus). */
    public function release(): void
    {
        DB::table('coc_accounts')->where('account_email_id', $this->id)->update(['account_email_id' => null]);
    }
}