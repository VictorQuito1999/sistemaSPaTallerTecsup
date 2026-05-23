<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentGroomingDetail extends Model
{
    protected $table = 'appointment_grooming_details';

    protected $fillable = [
        'appointment_id',
        'checklist',
        'actual_duration_min',
        'notes',
    ];

    protected $casts = [
        'checklist' => 'array',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
