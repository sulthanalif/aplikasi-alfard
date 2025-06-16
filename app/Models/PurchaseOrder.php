<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'invoice',
        'date',
        'total_price',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            $po->invoice = self::generateInvoice();
        });
    }

    public static function generateInvoice($int = 5): string
    {
        $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $date = date('Ymd');
        $randomString = substr(str_shuffle(str_repeat($string, $int)), 0, $int);

        return "PO{$date}{$randomString}";
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'purchase_order_id', 'id');
    }
}
