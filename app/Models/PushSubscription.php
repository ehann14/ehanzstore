<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu HP/browser admin yang mengaktifkan notifikasi push. */
class PushSubscription extends Model
{
    protected $fillable = ['user_id', 'endpoint_hash', 'endpoint', 'user_agent', 'last_sent_at'];

    protected $casts = ['last_sent_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashFor(string $endpoint): string
    {
        return sha1($endpoint);
    }
}