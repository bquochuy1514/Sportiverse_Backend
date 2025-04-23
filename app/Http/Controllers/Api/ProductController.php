<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sport;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
    */
    public function index(Request $request)
    {
        // Khởi tạo query builder với các quan hệ cần thiết
        $query = Product::with(['images', 'category', 'sport']);
        
        // Xử lý nhiều category_id
        if ($request->has('category_id')) {
            $categoryIds = is_array($request->category_id) 
                ? $request->category_id 
                : explode(',', $request->input('category_id'));
                
            $query->whereIn('category_id', $categoryIds);
        }
        
        // Xử lý nhiều sport_id
        if ($request->has('sport_id')) {
            $sportIds = is_array($request->sport_id) 
                ? $request->sport_id 
                : explode(',', $request->input('sport_id'));
                
            $query->whereIn('sport_id', $sportIds);
        }
        
        if ($request->has('limit')) {
            $query->take($request->limit);
        }

        if($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $products = $query->get();
        
        // Xử lý đường dẫn ảnh cho từng sản phẩm
        $products->each(function ($product) {
            // Xử lý ảnh cho bảng images
            if ($product->images) {
                $product->images->transform(function ($image) {
                    if ($image->image_path) {
                        $image->image_path = url('storage/' . $image->image_path);
                    }
                    return $image;
                });
            }
        });
        
        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách sản phẩm thành công',
            'data' => $products
        ]);
    }

    public function index2(Request $request)
    {
        // Khởi tạo query builder với các quan hệ cần thiết
        $query = Product::with(['images', 'category', 'sport'])
                        ->where('sale_price', '>', 0);
        
        // Xử lý nhiều category_id
        if ($request->has('category_id')) {
            $categoryIds = is_array($request->category_id) 
                ? $request->category_id 
                : explode(',', $request->input('category_id'));
                
            $query->whereIn('category_id', $categoryIds);
        }
        
        // Xử lý nhiều sport_id
        if ($request->has('sport_id')) {
            $sportIds = is_array($request->sport_id) 
                ? $request->sport_id 
                : explode(',', $request->input('sport_id'));
                
            $query->whereIn('sport_id', $sportIds);
        }
        
        if ($request->has('limit')) {
            $query->take($request->limit);
        }

        if($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $products = $query->get();
        
        // Xử lý đường dẫn ảnh cho từng sản phẩm
        $products->each(function ($product) {
            // Xử lý ảnh cho bảng images
            if ($product->images) {
                $product->images->transform(function ($image) {
                    if ($image->image_path) {
                        $image->image_path = url('storage/' . $image->image_path);
                    }
                    return $image;
                });
            }
        });
        
        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách sản phẩm thành công',
            'data' => $products
        ]);
    }

    public function getProductsThroughSportSlug(Request $request, string $slug) {
        $sport = Sport::where('slug', $slug)->first();

        if (!$sport) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy sản phẩm của môn thể thao này',
                'data' => null
            ], 404);
        }

        $query = Product::with(['images', 'category', 'sport'])
                        ->where('sport_id', $sport->id)
                        ->where('is_active', true);

        if ($request->has('is_featured')) {
            $isFeatured = filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_featured', $isFeatured);
        }

        // Xử lý lọc theo khoảng giá
        if ($request->has('price_min') && is_numeric($request->input('price_min'))) {
            $query->where('price', '>=', $request->input('price_min'));
        }

        if ($request->has('price_max') && is_numeric($request->input('price_max'))) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        // Xử lý tìm kiếm theo tên sản phẩm
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        // Xử lý sắp xếp
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Đảm bảo chỉ sắp xếp theo các cột hợp lệ
        $allowedSortColumns = ['created_at', 'name', 'price', 'sale_price', 'stock_quantity'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->input('per_page', 12); // Mặc định 12 sản phẩm mỗi trang
        $page = $request->input('page', 1);

        $products = $query->paginate($perPage, ['*'], 'page', $page);
        $total = $products->total();

        // Xử lý đường dẫn ảnh cho từng sản phẩm
        $products->each(function ($product) {
            if ($product->images) {
                $product->images->transform(function ($image) {
                    if ($image->image_path) {
                        $image->image_path = url('storage/' . $image->image_path);
                    }
                    return $image;
                });
            }
        });

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    public function featuredProducts(Request $request) 
    {
        $query = Product::with('images', 'category', 'sport')
            ->where('is_featured', 1)
            ->where('is_active', 1);

            // Kiểm tra nếu có category_id được truyền vào
        if ($request->has('category_id')) {
            $categoryId = $request->input('category_id');
            $query->where('category_id', $categoryId);
        }

        // Thêm các tùy chọn lọc khác (nếu cần)
        if ($request->has('sport_id')) {
            $query->where('sport_id', $request->input('sport_id'));
        }

        if ($request->has('limit')) {
            $query->take($request->limit);
        }

        $products = $query->get();
        
        // Xử lý đường dẫn ảnh cho từng sản phẩm
        $products->each(function ($product) {
            // Xử lý ảnh cho bảng images
            if ($product->images) {
                $product->images->transform(function ($image) {
                    if ($image->image_path) {
                        $image->image_path = url('storage/' . $image->image_path);
                    }
                    return $image;
                });
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách sản phẩm nổi bật thành công',
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
            'sport_id' => 'required|exists:categories,id',
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

        $sport = Category::find($request->sport_id);
        if (!$sport) {
            return response()->json([
                'status' => false,
                'message' => 'Sport not found'
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
            $product->sport_id = $request->sport_id;
            $product->name = $request->name;
            $product->slug = $slug;
            // $product->description = $request->description;
            $product->description = Purify::clean($request->description);
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
                    $productImage->image_path = $path;
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

    public function test() {
        return env('FRONTEND_URL');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $identifier)
    {
        // Kiểm tra xem identifier có phải là số (ID) hay không
        $product = is_numeric($identifier) 
            ? Product::with('category', 'images', 'sport')->find($identifier)
            : Product::with('category', 'images', 'sport')->where('slug', $identifier)->first();
        
        // Xử lý ảnh cho bảng images
        if ($product && $product->images) {
            $product->images->transform(function ($image) {
                if ($image->image_path) {
                    $image->image_path = url('storage/' . $image->image_path);
                }
                return $image;
            });
        }

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
    public function update(Request $request, string $productId)
    {
        // Validate dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'sport_id' => 'required|exists:categories,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'stock_quantity' => 'required|integer|min:0',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Tìm sản phẩm
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại'
            ], 404);
        }

        // Kiểm tra category và sport
        $category = Category::find($request->category_id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại'
            ], 404);
        }

        $sport = Category::find($request->sport_id);
        if (!$sport) {
            return response()->json([
                'status' => false,
                'message' => 'Môn thể thao không tồn tại'
            ], 404);
        }

        // Tạo slug mới nếu tên thay đổi
        $slug = $product->slug;
        if ($product->name !== $request->name) {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $count = 1;

            // Đảm bảo slug là duy nhất
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
        }

        DB::beginTransaction();

        try {
            // Cập nhật thông tin sản phẩm
            $product->category_id = $request->category_id;
            $product->sport_id = $request->sport_id;
            $product->name = $request->name;
            $product->slug = $slug;
            $product->description = Purify::clean($request->description);
            $product->price = $request->price;
            $product->sale_price = $request->sale_price;
            $product->stock_quantity = $request->stock_quantity;
            $product->is_featured = $request->is_featured ?? 0;
            $product->is_active = $request->is_active ?? 1;
            $product->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sản phẩm đã được cập nhật thành công',
                'data' => $product
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => false,
                'message' => 'Đã xảy ra lỗi khi cập nhật sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Tìm sản phẩm với relationship images
        $product = Product::with('images')->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }

        // Xóa tất cả các file ảnh từ storage
        foreach ($product->images as $image) {
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        // Xóa sản phẩm
        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Sản phẩm đã được xóa thành công',
        ]);
    }
}
