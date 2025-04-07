<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::query();
        
        // Lọc theo môn thể thao
        if ($request->has('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }
        
        // Lọc theo danh mục cha
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else if ($request->boolean('root_only', false)) {
            // Chỉ lấy danh mục gốc (không có parent)
            $query->whereNull('parent_id');
        }
        
        // Lọc theo trạng thái active
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        
        // Tìm kiếm theo tên
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Sắp xếp
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Lấy tất cả kết quả (không phân trang)
        $categories = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Xác thực dữ liệu đầu vào
            $fields = $request->validate([
                'sport_id' => 'required|exists:sports,id',
                'parent_id' => 'nullable|exists:categories,id',
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:categories',
                'is_active' => 'nullable|boolean',
            ]);
            
            // Tạo slug từ name nếu không được cung cấp
            if (empty($fields['slug'])) {
                $fields['slug'] = Str::slug($fields['name']);
                
                // Kiểm tra xem slug đã tồn tại chưa
                $count = 1;
                $originalSlug = $fields['slug'];
                while (Category::where('slug', $fields['slug'])->exists()) {
                    $fields['slug'] = $originalSlug . '-' . $count++;
                }
            }
            
            // Thiết lập giá trị mặc định cho is_active nếu không được cung cấp
            if (!isset($fields['is_active'])) {
                $fields['is_active'] = true;
            }
            
            // Tạo danh mục mới
            $category = Category::create($fields);
            
            return response()->json([
                'success' => true,
                'message' => 'Thêm danh mục thành công',
                'data' => $category
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực dữ liệu',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
