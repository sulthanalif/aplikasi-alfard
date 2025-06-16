<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrderDetail extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'price',
        'subtotal'
    ];


    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'purchase_order_id');
    }
}
