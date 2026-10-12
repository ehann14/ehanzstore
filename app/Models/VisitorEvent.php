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
        return $this->belongsTo(CocAccount::class, 'coc_account_id');
    }
}