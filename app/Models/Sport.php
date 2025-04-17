<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Sport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Tự động tạo slug từ name nếu không được cung cấp
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sport) {
            if (empty($sport->slug)) {
                $sport->slug = Str::slug($sport->name);
            }
        });
    }

    // Relationship với categories
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    
}
