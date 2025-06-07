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
        'is_active'
    ];

    // Kiểm tra mã có hợp lệ không
    public function isValid($orderAmount = 0)
    {
        if (!$this->is_active) return false;
        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) return false;
        return true;
    }

    // Quan hệ với đơn hàng
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Tính tiền giảm
    public function calculateDiscount($orderAmount)
    {
        if (!$this->isValid($orderAmount)) return 0;

        if ($this->type === 'percentage') {
            return ($orderAmount * $this->value) / 100;
        }
        
        return $this->value; // fixed amount
    }
}
