<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // database/seeders/RoleSeeder.php
    public function run()
    {
        $admin = Role::create(['name' => 'admin']);
        $groomer = Role::create(['name' => 'groomer']);
        $receptionist = Role::create(['name' => 'receptionist']);
        $customer = Role::create(['name' => 'customer']);

        // Ejemplo de permiso para el CRUD de empleados que mencionas
        Permission::create(['name' => 'gestionar empleados'])->assignRole($admin);
    }
}
