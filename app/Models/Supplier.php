<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'company_name',
        'nit',
        'phone',
        'email',
        'address',
        'credit_days',
    ];
}
