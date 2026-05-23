<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Breeds extends Model
{
    protected $table = 'breeds';

    protected $fillable = [
        'category_id',
        'name',
        'size',
        'duration_factor',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(species_categories::class, 'category_id');
    }

    public function pets()
    {
        return $this->hasMany(Pet::class, 'breed_id');
    }
}
