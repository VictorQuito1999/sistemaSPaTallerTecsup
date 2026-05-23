<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PetPhoto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pet_id',
        'appointment_id',
        'type',
        'file_path',
        'file_name',
    ];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
