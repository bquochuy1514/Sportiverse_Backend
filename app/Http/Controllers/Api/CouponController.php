<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    /**
     * Áp dụng mã giảm giá
     */
    public function applyCoupon(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
            'total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = $request->input('code');
        $totalAmount = $request->input('total_amount');

        // Tìm coupon
        $coupon = Coupon::where('code', $code)
            ->where('is_active', 1)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không hợp lệ hoặc đã bị vô hiệu hóa',
            ], 404);
        }

        // Kiểm tra giá trị đơn hàng tối thiểu
        if ($coupon->min_order_amount && $totalAmount < $coupon->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($coupon->min_order_amount, 0, ',', '.') . 'đ',
            ], 400);
        }

        // Tính giá trị giảm giá
        $discount = $coupon->type === 'fixed' ? $coupon->value : ($coupon->value / 100) * $totalAmount;

        // Trả về kết quả
        return response()->json([
            'success' => true,
            'message' => 'Áp dụng mã giảm giá thành công',
            'data' => [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'discount_amount' => $discount,
            ],
        ], 200);
    }

    /**
     * Lấy danh sách mã giảm giá
     */
    public function index()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách mã giảm giá thành công',
            'data' => $coupons,
        ], 200);
    }

    /**
     * Tạo mã giảm giá mới
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
            ], 422);
        }

        $coupon = Coupon::create(array_merge($request->all(), [
            'is_active' => $request->input('is_active', 1),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Tạo mã giảm giá thành công',
            'data' => $coupon,
        ], 201);
    }

    /**
     * Xóa mã giảm giá
     */
    public function destroy(string $id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại',
            ], 404);
        }

        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa mã giảm giá thành công',
        ], 200);
    }
}