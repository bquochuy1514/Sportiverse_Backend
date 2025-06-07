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

class OrderController extends Controller
{
    // Tạo đơn hàng mới
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_name' => 'required|string',
            'shipping_phone' => 'required|string',
            'shipping_address' => 'required|string',
            'coupon_code' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Tính tổng tiền đơn hàng
            $subtotal = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $unitPrice = $product->sale_price ?? $product->price;
                $itemSubtotal = $unitPrice * $item['quantity'];
                
                $subtotal += $itemSubtotal;
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal
                ];
            }

            // Xử lý mã giảm giá
            $discountAmount = 0;
            $couponId = null;

            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)->first();
                if ($coupon && $coupon->isValid($subtotal)) {
                    $discountAmount = $coupon->calculateDiscount($subtotal);
                    $couponId = $coupon->id;
                }
            }

            $finalAmount = $subtotal - $discountAmount;

            // Tạo đơn hàng
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_amount' => $finalAmount,
                'shipping_name' => $request->shipping_name,
                'shipping_phone' => $request->shipping_phone,
                'shipping_address' => $request->shipping_address,
                'notes' => $request->notes,
                'coupon_id' => $couponId,
                'discount_amount' => $discountAmount,
                'status' => 'pending',
                'payment_status' => 'pending'
            ]);

            // Tạo chi tiết đơn hàng
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công',
                'data' => $order->load(['orderItems.product', 'coupon'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    // Lấy danh sách đơn hàng của user
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
                      ->with(['orderItems.product', 'coupon'])
                      ->orderBy('created_at', 'desc')
                      ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // Xem chi tiết đơn hàng
    public function show($id)
    {
        $order = Order::where('id', $id)
                     ->where('user_id', auth()->id())
                     ->with(['orderItems.product', 'coupon'])
                     ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    // Hủy đơn hàng
    public function cancel($id)
    {
        $order = Order::where('id', $id)
                     ->where('user_id', auth()->id())
                     ->where('status', 'pending')
                     ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng này'
            ], 400);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy đơn hàng thành công'
        ]);
    }

    // Admin: Lấy tất cả đơn hàng
    public function adminIndex()
    {
        $orders = Order::with(['user', 'orderItems.product', 'coupon'])
                      ->orderBy('created_at', 'desc')
                      ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // Admin: Cập nhật trạng thái đơn hàng
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        $order = Order::find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $order
        ]);
    }
}
