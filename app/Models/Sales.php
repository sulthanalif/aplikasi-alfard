<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sales extends Model
{
    use SoftDeletes;

    protected $table = 'sales';

    protected $fillable = [
        'invoice',
        'date',
        'customer_id',
        'total_price',
        'approve_by',
        'address',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sales) {
            $sales->invoice = self::generateInvoice();
        });
    }

    public static function generateInvoice($int = 5): string
    {
        $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $date = date('Ymd');
        $randomString = substr(str_shuffle(str_repeat($string, $int)), 0, $int);

        return "INV{$date}{$randomString}";
    }



    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id', 'customer_id');
    }

    public function approveBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approve_by', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(SalesDetail::class, 'sales_id', 'id');
    }
}
