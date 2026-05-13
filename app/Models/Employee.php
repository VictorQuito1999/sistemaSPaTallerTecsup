<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'ci',
        'shift',
        'specialty',
        'concurrent_capacity',
        'calendar_color',
    ];

    protected function casts(): array
    {
        return [
            'concurrent_capacity' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
