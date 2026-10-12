<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'transaction_code', 'coc_account_id', 'account_code_snapshot',
        'sale_price', 'transaction_status', 'payment_status',
        'processed_by', 'sold_at', 'notes'
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(CocAccount::class, 'coc_account_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}