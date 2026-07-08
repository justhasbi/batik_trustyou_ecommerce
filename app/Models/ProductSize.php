<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'stock',
        'min_height',
        'max_height',
        'min_weight',
        'max_weight',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
