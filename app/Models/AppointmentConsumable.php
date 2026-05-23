<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentConsumable extends Model
{
    protected $table = 'appointment_consumables';

    protected $fillable = [
        'appointment_id',
        'product_id',
        'quantity_used',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
