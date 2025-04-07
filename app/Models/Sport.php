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

            // Thêm domain vào icon nếu chưa có
            if ($sport->icon && !Str::startsWith($sport->icon, ['http://', 'https://'])) {
                $sport->icon = 'http://localhost:8000/storage/sports/' . $sport->icon;
            }
        });

        static::updating(function ($sport) {
            // Thêm domain vào icon nếu chưa có
            if ($sport->icon && !Str::startsWith($sport->icon, ['http://', 'https://'])) {
                $sport->icon = 'http://localhost:8000/storage/sports/' . $sport->icon;
            }
        });
    }

    // Relationship với categories
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    
}
