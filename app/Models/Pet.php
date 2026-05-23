<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $fillable = [
        'customer_id',
        'breed_id',
        'name',
        'gender',
        'birth_date',
        'weight_kg',
        'allergies',
        'temperament',
        'is_active',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function breed()
    {
        return $this->belongsTo(Breeds::class, 'breed_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function vaccinations()
    {
        return $this->hasMany(PetVaccination::class);
    }

    public function photos()
    {
        return $this->hasMany(PetPhoto::class);
    }
}

