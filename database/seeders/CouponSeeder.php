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
            'max_discount_amount' => 50.00,
            'start_date' => '2025-04-15',
            'end_date' => '2025-06-30',
            'usage_limit' => 20,
            'used_count' => 0,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'FIXED2025',
            'type' => 'fixed',
            'value' => 30.00,
            'min_order_amount' => 150.00,
            'max_discount_amount' => null,
            'start_date' => '2025-05-01',
            'end_date' => '2025-07-31',
            'usage_limit' => 30,
            'used_count' => 0,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Coupon::create([
                'code' => 'COUPON' . $i . '_' . now()->year,
                'type' => $i % 2 == 0 ? 'percentage' : 'fixed',
                'value' => $i * 10.00,
                'min_order_amount' => $i * 50.00,
                'max_discount_amount' => $i % 2 == 0 ? $i * 20.00 : null,
                'start_date' => now()->addDays($i),
                'end_date' => now()->addMonths($i),
                'usage_limit' => 100 + $i * 10,
                'used_count' => 0,
                'is_active' => true,
            ]);
        }
    }
}
