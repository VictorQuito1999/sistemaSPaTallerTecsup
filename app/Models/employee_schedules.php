<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class employee_schedules extends Model
{
    protected $table = 'employee_schedules';

    protected $fillable = [
        'employee_id',
        'day_of_week',
        'start_time',
        'end_time',
        'lunch_start',
        'is_available',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
