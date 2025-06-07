<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $products = Product::take(2)->get();

        if ($user && $products->count() > 0) {
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => 500000,
                'shipping_name' => 'Nguyễn Văn A',
                'shipping_phone' => '0123456789',
                'shipping_address' => '123 Đường ABC, Quận 1, TP.HCM',
                'status' => 'pending',
                'payment_status' => 'pending',
                'discount_amount' => 0
            ]);

            foreach ($products as $product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->price,
                    'subtotal' => $product->price
                ]);
            }
        }
    }
}
