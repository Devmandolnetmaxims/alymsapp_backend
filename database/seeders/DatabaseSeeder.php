<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\PermissionSeed;
use Database\Seeders\company_types;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\ServiceSeed;
use Database\Seeders\ModuleSeed;
use Database\Seeders\RoleSeed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeed::class);
        $this->call(PermissionSeed::class);
        $this->call(company_types::class);
        $this->call(ModuleSeed::class);
        $this->call(ServiceSeed::class);
        $user = \App\Models\User::factory()->create();
        $user->assignRole('Super Admin');

        // \App\Models\User::factory()->create([
        //     'name' => 'Admin',
        //     'email' => 'Admin@gmail.com',
        //     'email_verified_at' => now(),
        //     'password' => static::$password ??= Hash::make('Admin!@#123'),
        //     'remember_token' => Str::random(10),
        // ]);
    }
}
