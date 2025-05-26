<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'status'
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
