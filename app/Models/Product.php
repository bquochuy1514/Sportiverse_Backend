<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'sale_price',
        'stock_quantity',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getPrimaryImage()
    {
        // Lọc mảng images để tìm ảnh chính
        $primaryImage = $this->images->where('is_primary', 1)->first();
        
        // Nếu không có ảnh chính, lấy ảnh đầu tiên (nếu có)
        if (!$primaryImage && $this->images->count() > 0) {
            $primaryImage = $this->images->first();
        }
        
        return $primaryImage->image_path;
    }

    /**
     * Get the category that owns the product.
    */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Get the images for the product.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the reviews for the product.
    */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the wishlist entries for the product.
    */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get the cart items for the product.
    */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the order items for the product.
    */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
