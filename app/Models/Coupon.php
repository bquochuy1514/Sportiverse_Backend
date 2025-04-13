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

    /**
     * Get the type attribute in Vietnamese.
     */
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === 'percentage' ? 'Phần trăm' : 'Số tiền cố định',
            set: fn ($value) => $value,
        );
    }

}
