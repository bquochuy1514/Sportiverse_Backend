<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'payment_status',
        'notes',
        'coupon_id',
        'discount_amount'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Tính tổng tiền trước khi giảm giá
    public function getSubtotalAttribute()
    {
        return $this->total_amount + $this->discount_amount;
    }
}
