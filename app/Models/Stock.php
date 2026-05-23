<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'inv_stocks';

    protected $fillable = [
        'product_id',
        'current_quantity',
        'last_update',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
