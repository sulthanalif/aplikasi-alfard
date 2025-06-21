<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDetail extends Model
{
    use SoftDeletes;

    protected $table = 'sales_details';

    protected $fillable = [
        'sales_id', 'product_id', 'quantity', 'subtotal',
    ];

    // protected $appends = [
    //     'net_subtotal',
    // ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getNetSubtotalAttribute()
    {
        return $this->subtotal - ($this->product->purchase_price * $this->quantity);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }
}
