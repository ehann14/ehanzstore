<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CocAccountImage extends Model
{
    protected $fillable = [
        'coc_account_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    /** URL publik foto: /uploads/accounts/xxx.jpg */
    public function getUrlAttribute(): string
    {
        return upload_url($this->image_path);
    }

    public function account()
    {
        return $this->belongsTo(CocAccount::class, 'coc_account_id');
    }
}
