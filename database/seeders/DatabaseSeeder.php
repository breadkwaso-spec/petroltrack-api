<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Pump;
use App\Models\FuelGrade;
use App\Models\FuelPrice;
use App\Models\FuelStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Initial Active Owner Account
        $owner = User::create([
            'employee_id' => 'OWN-001',
            'full_name' => 'Station Owner',
            'password' => Hash::make('password123'),
            'role' => 'Owner',
            'status' => 'Active',
        ]);

        // 2. Create Sample Active Attendant Account
        User::create([
            'employee_id' => 'ATT-001',
            'full_name' => 'Juan Dela Cruz',
            'password' => Hash::make('password123'),
            'role' => 'Attendant',
            'status' => 'Active',
            'approved_by' => $owner->id,
        ]);

        // 3. Create Pumps (Pump 01 - Pump 04)
        foreach (['Pump 01', 'Pump 02', 'Pump 03', 'Pump 04'] as $pumpNo) {
            Pump::create(['pump_no' => $pumpNo, 'active' => true]);
        }

        // 4. Create Fuel Grades, Initial Prices (PHP), and Initial Stock
        $grades = [
            ['name' => 'Unleaded', 'price' => 58.50, 'stock' => 2500.00],
            ['name' => 'Premium',  'price' => 61.25, 'stock' => 2000.00],
            ['name' => 'Diesel',   'price' => 54.00, 'stock' => 3000.00],
        ];

        foreach ($grades as $item) {
            $grade = FuelGrade::create(['name' => $item['name'], 'active' => true]);

            FuelPrice::create([
                'grade_id' => $grade->id,
                'price_per_liter' => $item['price'],
                'effective_from' => now(),
                'set_by' => $owner->id,
            ]);

            FuelStock::create([
                'grade_id' => $grade->id,
                'liters_on_hand' => $item['stock'],
                'low_stock_threshold' => 500.00,
            ]);
        }
    }
}