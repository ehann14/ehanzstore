<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catatan internal super admin tentang seorang pelanggan (dikenali dari nomor WhatsApp).
 */
class CustomerNote extends Model
{
    protected $fillable = ['phone', 'note', 'updated_by'];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}