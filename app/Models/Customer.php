<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'user_id',
        'ci',
        'customer_group_id',
        'status',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return $this->user ? $this->user->first_name . ' ' . $this->user->last_name : '';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pets()
    {
        return $this->hasMany(Pet::class);
    }
}
