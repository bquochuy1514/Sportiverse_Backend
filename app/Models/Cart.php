<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id'];

    /**
     * Lấy tất cả các mục trong giỏ hàng
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Lấy người dùng sở hữu giỏ hàng
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tính tổng số lượng items trong giỏ hàng
     * 
     * @return int
     */
    public function getTotalItemsCount()
    {
        return $this->items->sum('quantity');
    }

    /**
     * Tính tổng giá trị giỏ hàng
     * 
     * @return float
     */
    public function getSubtotal()
    {
        $subtotal = 0;
        
        foreach ($this->items as $item) {
            $currentPrice = $item->product->getCurrentPrice();
            $subtotal += $currentPrice * $item->quantity;
        }
        
        return $subtotal;
    }
}