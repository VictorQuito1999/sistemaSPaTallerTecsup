<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'type',
        'base_duration_min',
        'base_price',
        'checklist_template',
        'allows_double_booking',
        'is_active',
    ];

    protected $casts = [
        'checklist_template' => 'array',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function consumables()
    {
        return $this->belongsToMany(Product::class, 'service_consumables')
            ->withPivot('estimated_quantity')
            ->withTimestamps();
    }
}
