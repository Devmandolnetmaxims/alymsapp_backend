<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // For estimate
        $permission = Permission::create(['name' => 'Add/Edit Estimate', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Show Estimate', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Delete Estimate', 'guard_name' => "api"]);
        // for job
        $permission = Permission::create(['name' => 'Add/Edit Job', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Show Job', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Delete Job', 'guard_name' => "api"]);
        // For team
        $permission = Permission::create(['name' => 'Add/Edit Team', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Show Team', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Delete Team', 'guard_name' => "api"]);
        // For customer
        $permission = Permission::create(['name' => 'Add/Edit Customer', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Show Customer', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Delete Customer', 'guard_name' => "api"]);
        // For services
        $permission = Permission::create(['name' => 'Add/Edit Services', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Show Services', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Delete Services', 'guard_name' => "api"]);
        // For comment
        $permission = Permission::create(['name' => 'Add/Edit Comment', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Show Comment', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Delete Comment', 'guard_name' => "api"]);
        // for invoice
        $permission = Permission::create(['name' => 'Add/Edit Invoice', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Show Invoice', 'guard_name' => "api"]);
        $permission = Permission::create(['name' => 'Delete Invoice', 'guard_name' => "api"]);
    }
}
