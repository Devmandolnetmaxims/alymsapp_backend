<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Service::create(['service' => 'PARTS ', 'company_type' => 1, 'rate' => 100]);
        Service::create(['service' => 'PAINT & MATERIAL', 'company_type' => 1, 'rate' => 150]);
        Service::create(['service' => 'REPAIR & PAINT', 'company_type' => 1, 'rate' => 200]);
        Service::create(['service' => 'PENALS', 'company_type' => 2, 'rate' => 120]);
        Service::create(['service' => 'BLENDS', 'company_type' => 2, 'rate' => 130]);
        Service::create(['service' => 'FITTINGS', 'company_type' => 2, 'rate' => 140]);
        Service::create(['service' => 'POLISH', 'company_type' => 2, 'rate' => 110]);
        Service::create(['service' => 'WHEELS', 'company_type' => 2, 'rate' => 160]);
        Service::create(['service' => 'EXTRA WORK', 'company_type' => 2, 'rate' => 170]);
        Service::create(['service' => 'PARTS', 'company_type' => 2, 'rate' => 180]);
    }
}
