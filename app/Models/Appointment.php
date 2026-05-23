<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    /** Alertas de inventario tras pasar a finished (no persistido). */
    public ?array $inventory_alerts = null;

    protected $fillable = [
        'pet_id',
        'employee_id',
        'service_id',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'agreed_price',
        'payment_type',
        'notes',
    ];


    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function groomingDetail()
    {
        return $this->hasOne(AppointmentGroomingDetail::class);
    }

    public function consumables()
    {
        return $this->hasMany(AppointmentConsumable::class);
    }
}
