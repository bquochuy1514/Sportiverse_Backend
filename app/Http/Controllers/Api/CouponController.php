<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    // Kiểm tra mã giảm giá
    public function checkCoupon(Request $request)
    {
        $coupon = Coupon::where('code', $request->coupon_code)->first();
        
        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại'
            ]);
        }

        $orderAmount = $request->order_amount;
        
        if (!$coupon->isValid($orderAmount)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không hợp lệ hoặc đơn hàng chưa đủ điều kiện'
            ]);
        }

        $discount = $coupon->calculateDiscount($orderAmount);
        
        return response()->json([
            'success' => true,
            'message' => 'Áp dụng mã giảm giá thành công',
            'discount_amount' => $discount,
            'final_amount' => $orderAmount - $discount,
            'coupon_id' => $coupon->id
        ]);
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách mã giảm giá thành công',
            'coupons' => $coupons
        ], 200);
    }

    // Tạo mã giảm giá mới
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:coupons',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0'
        ]);

        $coupon = Coupon::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Tạo mã giảm giá thành công',
            'data' => $coupon
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Lấy mã giảm giá thành công',
            'coupon' => $coupon
        ], 200);
    }

    // Xóa mã giảm giá
    public function destroy($id)
    {
        Coupon::find($id)->delete();
        return response()->json([
            'success' => true, 
            'message' => 'Đã xóa mã giảm giá thành công'
        ]);
    }
}
