<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('images', 'category')->get();
        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách sản phẩm thành công',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'stock_quantity' => 'required|integer|min:0',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'images' => 'required|nullable|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
            'primary_image_index' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kiểm tra category có tồn tại không
        $category = Category::find($request->category_id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }

        // Tạo slug từ tên sản phẩm
        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;

        // Đảm bảo slug là duy nhất
        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        DB::beginTransaction();

        try {
            // Tạo sản phẩm mới
            $product = new Product();
            $product->category_id = $request->category_id;
            $product->name = $request->name;
            $product->slug = $slug;
            $product->description = $request->description;
            $product->price = $request->price;
            $product->sale_price = $request->sale_price;
            $product->stock_quantity = $request->stock_quantity;
            $product->is_featured = $request->is_featured ?? 0;
            $product->is_active = $request->is_active ?? 1;
            $product->save();

            // Xử lý upload nhiều hình ảnh
            $uploadedImages = [];
            
            if ($request->hasFile('images')) {
                $primaryImageIndex = $request->input('primary_image_index', 0);
                
                foreach ($request->file('images') as $index => $imageFile) {
                    $path = $imageFile->store('products', 'public');
                    
                    $productImage = new ProductImage();
                    $productImage->product_id = $product->id;
                    $productImage->image_path = asset('storage/' . $path);
                    $productImage->is_primary = ($index == $primaryImageIndex) ? 1 : 0;
                    $productImage->save();
                    
                    $uploadedImages[] = [
                        'id' => $productImage->id,
                        'image_path' => $productImage->image_path,
                        'is_primary' => $productImage->is_primary
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sản phẩm đã được tạo thành công',
                'data' => [
                    'product' => $product,
                    'images' => $uploadedImages
                ]
            ], 201);
            return 'không có ảnh';
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Xóa các file đã upload nếu có lỗi
            if (isset($uploadedImages) && count($uploadedImages) > 0) {
                foreach ($uploadedImages as $image) {
                    $path = str_replace(asset('storage/'), '', $image['image_path']);
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
            
            return response()->json([
                'status' => false,
                'message' => 'Đã xảy ra lỗi khi tạo sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category', 'images')->find($id);
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }
        return response()->json([
            'status' => true,
            'message' => 'Lấy sản phẩm thành công',
            'data' => $product,
        ]);
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
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }

        // Xóa sản phẩm
        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Sản phẩm đã được xóa thành công',
        ]);
    }
}
