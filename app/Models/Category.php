<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Category extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể gán hàng loạt
     */
    protected $fillable = [
        'sport_id',
        'parent_id',
        'name',
        'slug',
        'image',
        'is_active'
    ];

    /**
     * Các thuộc tính nên cast
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot model
     */
    protected static function boot()
    {
        parent::boot();

        // Tự động tạo slug từ name nếu không được cung cấp
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Quan hệ với Sport
     */
    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Quan hệ với danh mục cha
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Quan hệ với các danh mục con
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Quan hệ với các sản phẩm
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
