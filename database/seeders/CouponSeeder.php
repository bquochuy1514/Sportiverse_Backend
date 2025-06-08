<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Coupon::create([
            'code' => 'SUMMER2025',
            'type' => 'percentage',
            'value' => 20.00,
            'min_order_amount' => 100.00,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'FIXED2025',
            'type' => 'fixed',
            'value' => 30.00,
            'min_order_amount' => 150.00,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Coupon::create([
                'code' => 'COUPON' . $i . '_' . now()->year,
                'type' => $i % 2 == 0 ? 'percentage' : 'fixed',
                'value' => $i * 10.00,
                'min_order_amount' => $i * 50.00,
                'is_active' => true,
            ]);
        }
    }
}
