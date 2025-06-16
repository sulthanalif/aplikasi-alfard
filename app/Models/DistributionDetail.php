<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionDetail extends Model
{
    protected $table = 'distribution_details';

    protected $fillable = [
        'distribution_id',
        'sales_id',
    ];

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class, 'distribution_id', 'id');
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sales_id', 'id');
    }
}
