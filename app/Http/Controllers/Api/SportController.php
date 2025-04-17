<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SportController extends Controller
{
    /**
    * Lấy danh sách tất cả môn thể thao
    */
    public function index(Request $request)
    {
        $query = Sport::query();

        // Sắp xếp
        $query->orderBy($request->get('sort_by', 'id'), $request->get('sort_direction', 'asc'));

        // Lấy tất cả dữ liệu
        $sports = $query->get();

        // Xử lý đường dẫn icon cho từng bản ghi
        $sports->transform(function ($sport) {
            if ($sport->icon) {
                // Thêm domain vào đường dẫn icon
                $sport->icon = url('storage/' . $sport->icon);
            }
            return $sport;
        });

        return response()->json([
            'success' => true,
            'data' => $sports
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
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:sports',
                'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
            ]);

            // Tạo slug từ name nếu không được cung cấp
            if (empty($fields['slug'])) {
                $fields['slug'] = Str::slug($fields['name']);
            }

            $fields['is_active'] = true;

            // Xử lý upload icon nếu có
            if ($request->hasFile('icon')) {
                $iconFile = $request->file('icon');
                $filename = Str::slug($fields['name']) . '-' . time() . '.' . $iconFile->getClientOriginalExtension();
                
                // Lưu file vào thư mục storage/app/public/sports
                Storage::disk('public')->putFileAs('sports', $iconFile, $filename);
                
                // Lưu đường dẫn vào database
                $fields['icon'] = 'sports/' . $filename;
            }

            // Tạo môn thể thao mới
            $sport = Sport::create($fields);

            return response()->json([
                'success' => true,
                'message' => 'Thêm môn thể thao thành công',
                'data' => $sport
            ], 201);
        } 
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực dữ liệu',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm môn thể thao',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
        // Tìm môn thể thao theo ID
        $sport = Sport::findOrFail($id);
        
        // Trả về thông tin môn thể thao
        return response()->json([
            'success' => true,
            'data' => $sport
        ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Xử lý khi không tìm thấy môn thể thao
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy môn thể thao với ID: ' . $id
            ], 404);
        } catch (\Exception $e) {
            // Xử lý các lỗi khác
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin môn thể thao',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Tìm môn thể thao cần cập nhật
            $sport = Sport::findOrFail($id);
            
            // Xác thực dữ liệu đầu vào
            $fields = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:sports,slug,' . $id,
                'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'description' => 'sometimes|nullable|string',
                'is_active' => 'sometimes|boolean',
            ]);
            
            // Tạo slug từ name nếu name được cập nhật và slug không được cung cấp
            if (isset($fields['name']) && !isset($fields['slug'])) {
                $fields['slug'] = Str::slug($fields['name']);
            }
            
            // Xử lý icon nếu được cung cấp
            if (isset($fields['icon']) && !empty($fields['icon'])) {
                // Lưu tên icon cũ để xóa sau
                $oldIcon = $sport->icon;

                // Upload icon mới
                $iconFile = $request->file('icon');
                $filename = Str::slug($fields['name']) . '-' . time() . '.' . $iconFile->getClientOriginalExtension();

                // Lưu file vào thư mục storage/app/public/sports
                Storage::disk('public')->putFileAs('sports', $iconFile, $filename);
                
                // Lưu đường dẫn vào database
                $fields['icon'] = $filename;

                // Xử lý icon cũ để lấy tên file
                if ($oldIcon) {
                    // Nếu icon là URL đầy đủ hoặc đường dẫn tương đối, trích xuất tên file
                    if (Str::contains($oldIcon, '/')) {
                        // Lấy phần cuối cùng của đường dẫn (tên file)
                        $oldIcon = basename($oldIcon);
                    }
                }
                Storage::disk('public')->delete('sports/' . $oldIcon);
            }

            
            // Cập nhật thông tin môn thể thao
            $sport->update($fields);
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật môn thể thao thành công',
                'data' => $sport
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy môn thể thao với ID: ' . $id
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực dữ liệu',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật môn thể thao',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Tìm môn thể thao cần xóa
            $sport = Sport::findOrFail($id);
            
            // Kiểm tra xem môn thể thao có danh mục con không
            if ($sport->categories()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa môn thể thao này vì có danh mục con liên quan',
                    'data' => [
                        'categories_count' => $sport->categories()->count()
                    ]
                ], 400);
            }
            
            // Lưu thông tin icon để xóa sau
            $icon = $sport->icon;
            
            // Xóa môn thể thao
            $sport->delete();
            
            // Xóa file icon nếu có
            if ($icon) {
                // Trích xuất tên file từ đường dẫn icon
                if (Str::contains($icon, '/')) {
                    $icon = basename($icon);
                } 
                // Kiểm tra và xóa file
                if (Storage::disk('public')->exists('sports/' . $icon)) {
                    Storage::disk('public')->delete('sports/' . $icon);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Xóa môn thể thao thành công'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy môn thể thao với ID: ' . $id
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa môn thể thao',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
