<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount', 
        'start_date',
        'end_date',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string', // Đảm bảo type được xử lý như chuỗi
        'value' => 'decimal:2', // Định dạng value với 2 chữ số thập phân
        'min_order_amount' => 'decimal:2', // Định dạng min_order_amount
        'max_discount_amount' => 'decimal:2', // Định dạng max_discount_amount
        'start_date' => 'date', // Cast thành đối tượng DateTime
        'end_date' => 'date', // Cast thành đối tượng DateTime
        'is_active' => 'boolean', // Cast thành boolean
        'created_at' => 'datetime', // Cast timestamp
        'updated_at' => 'datetime', // Cast timestamp
    ];

    // Quan hệ với đơn hàng
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isValid() {
        // Kiểm tra trạng thái hoạt động
        if(!$this->is_active) {
            return false;
        }

        // Kiểm tra giới hạn sử dụng
        if($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        // Kiểm tra thời hạn
        $today = now()->startOfDay();

        if ($this->start_date && $today->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $today->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    // Tính toán số tiền giảm giá
    public function calculateDiscount($orderTotal)
    {
        if (!$this->isValid()) {
            return 0;
        }

        // Kiểm tra giá trị đơn hàng tối thiểu
        if ($this->min_order_amount && $orderTotal < $this->min_order_amount) {
            return 0;
        }

        $discount = 0;
        
        if ($this->type === 'percentage') {
            $discount = $orderTotal * ($this->value / 100);
            
            // Áp dụng giới hạn giảm giá tối đa nếu có
            if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
                $discount = $this->max_discount_amount;
            }
        } else { // fixed
            $discount = $this->value;
            
            // Đảm bảo giảm giá không vượt quá tổng đơn hàng
            if ($discount > $orderTotal) {
                $discount = $orderTotal;
            }
        }

        return $discount;
    }

    // Tăng số lượt sử dụng
    public function incrementUsage()
    {
        $this->used_count++;
        $this->save();
    }
}
