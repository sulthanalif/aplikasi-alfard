<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'sales_id',
        'customer_id',
        'status',
    ];

    protected $casts = [
        'remaining' => 'float',
    ];

    public function getRemainingAttribute(): float
    {
        return $this->details() ? $this->sales->total_price - $this->details->sum('amount') : $this->sales->total_price;
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sales_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id', 'customer_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PaymentDetail::class, 'payment_id', 'id');
    }
}
