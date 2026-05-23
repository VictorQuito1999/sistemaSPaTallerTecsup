<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PetVaccination extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pet_id',
        'vaccine_type',
        'application_date',
        'expiry_date',
        'clinic_name',
        'observations',
    ];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}
