<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::create(['name' => 'super admin', 'label' => 'Super Admin', 'guard_name' => "api"]);
        $role = Role::create(['name' => 'Chief Technician', 'label' => 'Chief Technician', 'guard_name' => "api"]);
        $role = Role::create(['name' => 'Technician', 'label' => 'Technician', 'guard_name' => "api"]);
        $role = Role::create(['name' => 'In House Worker', 'label' => 'In House Worker', 'guard_name' => "api"]);
        $role = Role::create(['name' => 'Out Source Worker', 'label' => 'Out Source Worker', 'guard_name' => "api"]);
    }
}