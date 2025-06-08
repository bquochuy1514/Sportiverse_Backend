<?php
// app/Http/Controllers/OrderController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;

class OrderController extends Controller
{
    public function placeOrder(Request $request) {
        // Validate input data
        $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'coupon_code' => 'nullable|string',
        ]);

        $user = Auth::user();
        // Get user's cart items with product details
        $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

        // Calculate total amount
        $totalAmount = $cart->items->reduce(function ($total, $item) {
            $price = $item->product->sale_price > 0 ? $item->product->sale_price : $item->product->price;
            return $total + ($price * $item->quantity);
        }, 0);

        // Handle coupon
        $discountAmount = 0;
        $coupon = null;
        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();
            if ($coupon->type === 'fixed') {
                $discountAmount = (int)$coupon->value;
            } elseif ($coupon->type === 'percentage') {
                $discountAmount = ($coupon->value / 100) * $totalAmount;
            }
        }

        $finalAmount = $totalAmount - $discountAmount;

        // Create order and order items in a transaction
        try {
            $order = DB::transaction(function () use ($user, $cart, $request, $totalAmount, $discountAmount, $finalAmount, $coupon) {
                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'total_amount' => $finalAmount,
                    'shipping_name' => $request->shipping_name,
                    'shipping_phone' => $request->shipping_phone,
                    'shipping_address' => $request->shipping_address,
                    'coupon_id' => $coupon ? $coupon->id : null,
                    'discount_amount' => $discountAmount,
                ]);

                // Create order items
                foreach ($cart->items as $item) {
                    $price = $item->product->sale_price > 0 ? $item->product->sale_price : $item->product->price;
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $price,
                        'subtotal' => $price * $item->quantity,
                    ]);
                }

                // Clear cart items
                $cart->items()->delete();

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công',
                'data' => [
                    'order_id' => $order->id,
                    'total_amount' => $order->total_amount,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Có lỗi khi đặt hàng: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all orders of the authenticated user
     */
    public function getUserOrders(Request $request)
    {
        // Check if user is authenticated
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để xem đơn hàng'], 401);
        }

        // Get all orders with order items and product details
        $orders = Order::where('user_id', $user->id)
            ->with(['orderItems.product', 'coupon'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform data for response
        $ordersData = $orders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'discount_amount' => $order->discount_amount,
                'coupon_code' => $order->coupon ? $order->coupon->code : null,
                'shipping_name' => $order->shipping_name,
                'shipping_phone' => $order->shipping_phone,
                'shipping_address' => $order->shipping_address,
                'created_at' => $order->created_at->toDateTimeString(),
                'order_items' => $order->orderItems->map(function ($item) {
                    $product = Product::with(['images', 'category', 'sport'])
                                    ->where('id', $item->product_id)
                                    ->first();
                    $productImage = url('storage/', $product->images[0]->image_path);
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'image' => $productImage ?? null,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách đơn hàng thành công',
            'data' => $ordersData,
        ], 200);
    }

}
