<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@petspa.local')],
            [
                'first_name' => 'Admin',
                'last_name' => 'PetSpa',
                'phone' => '00000000',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'PetSpa#Admin1')),
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]
        );
    }
}
