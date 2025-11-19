<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CompanyType;

class company_types extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanyType::create(['name' => 'Private/Insurance']);
        CompanyType::create(['name' => 'Trade Contact']);
    }
}
