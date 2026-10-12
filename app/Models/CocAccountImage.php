<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CocAccountImage extends Model
{
    protected $fillable = ['coc_account_id', 'image_path', 'is_primary', 'sort_order'];

    public function account()
    {
        return $this->belongsTo(CocAccount::class, 'coc_account_id');
    }
}