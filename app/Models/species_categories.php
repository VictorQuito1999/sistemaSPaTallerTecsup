<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class species_categories extends Model
{
    protected $table = 'species_categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public function breeds()
    {
        return $this->hasMany(Breeds::class, 'category_id');
    }
}
