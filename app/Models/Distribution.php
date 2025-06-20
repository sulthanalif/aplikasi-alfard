<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distribution extends Model
{
    protected $table = 'distributions';

    protected $fillable = [
        'number',
        'date',
        'user_id',
        'status',
    ];

    protected $appends = [
        'status_text',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($distribution) {
            $distribution->number = self::generateNumber();
        });
    }

    public static function generateNumber($int = 5): string
    {
        $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $date = date('Ymd');
        $randomString = substr(str_shuffle(str_repeat($string, $int)), 0, $int);

        return "DIS{$date}{$randomString}";
    }

    public function getStatusTextAttribute()
    {
        if (!$this->status) {
            return 'pending';
        }
    
        if ($this->status) {
            // Check if all details are delivered
            $allDelivered = $this->details()->count() > 0 && 
                            $this->details()->where('status', '!=', 'delivered')->count() === 0;
        
            return $allDelivered ? 'success' : 'process';
        }
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DistributionDetail::class, 'distribution_id', 'id');
    }
}
