<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $quantity = $request->quantity ?? 1;

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Kiểm tra sản phẩm có tồn tại và còn hàng không
        $product = Product::findOrFail($request->product_id);
        
        // Kiểm tra số lượng tồn kho
        if ($product->stock_quantity < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Số lượng sản phẩm không đủ trong kho'
            ], 422);
        }

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        try {
            // Lấy hoặc tạo giỏ hàng cho user hiện tại
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

            // Kiểm tra sản phẩm đã tồn tại trong giỏ hàng chưa
            $cartItem = $cart->items()->where('product_id', $request->product_id)->first();

            if ($cartItem) {
                // Cập nhật số lượng nếu sản phẩm đã tồn tại
                $newQuantity = $cartItem->quantity + $quantity;
                
                // Kiểm tra lại số lượng tồn kho
                if ($product->stock_quantity < $newQuantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Số lượng sản phẩm không đủ trong kho'
                    ], 422);
                }

                $cartItem->update(['quantity' => $newQuantity]);
            } else {
                // Thêm sản phẩm mới vào giỏ hàng
                $cart->items()->create([
                    'product_id' => $request->product_id,
                    'quantity' => $quantity
                ]);
            }

            // Tải lại giỏ hàng với dữ liệu mới
            $cart = $cart->fresh(['items', 'items.product', 'items.product.images']);

            // Định dạng dữ liệu giỏ hàng
            $formattedCart = $this->formatCartData($cart);
            return $formattedCart;

            return response()->json([
                'success' => true,
                'message' => 'Sản phẩm đã được thêm vào giỏ hàng',
                'data' => $formattedCart
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi thêm sản phẩm vào giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * Hiển thị thông tin giỏ hàng của người dùng hiện tại
     *
     * @return \Illuminate\Http\JsonResponse
    */
    public function show()
    {
        try {
            // Lấy giỏ hàng của người dùng hiện tại
            $cart = Cart::with(['items.product.category.sport'])
                    ->where('user_id', Auth::id())
                    ->first();
            
            // Nếu không tìm thấy giỏ hàng, trả về giỏ hàng trống
            if (!$cart) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => null,
                        'user_id' => Auth::id(),
                        'items' => [],
                        'item_count' => 0,
                        'subtotal' => 0,
                        'total' => 0
                    ]
                ]);
            }
            
            // Định dạng dữ liệu giỏ hàng
            $formattedCart = $this->formatCartData($cart);
            
            return response()->json([
                'success' => true,
                'data' => $formattedCart
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi lấy thông tin giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Định dạng dữ liệu giỏ hàng
     *
     * @param \App\Models\Cart $cart
     * @return array
     */
    private function formatCartData(Cart $cart)
    {
       // Đảm bảo các mối quan hệ đã được load
        if (!$cart->relationLoaded('items')) {
            $cart->load(['items', 'items.product']);
        }

        $items = $cart->items->map(function ($item) {
            $product = $item->product;
            if($product->sale_price > 0) {
                $currentPrice = $product->sale_price;
            } else {
                $currentPrice = $product->price;
            }

            return [
                'id' => $item->id,
                'product_id' => $product->id,
                'product' => $item->product,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => url('/storage', $product->getPrimaryImage()),
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'quantity' => $item->quantity,
                'subtotal' => $currentPrice * $item->quantity,
                'stock_quantity' => $product->stock_quantity,
            ];
        });
        
        $subtotal = $this->calculateCartTotal($cart);

        return [
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'items' => $items,
            'item_count' => $items->sum('quantity'),
            'subtotal' => $subtotal,
            'total' => $subtotal
        ];
    }

    /**
     * Tính tổng giá trị giỏ hàng
     *
     * @param \App\Models\Cart $cart
     * @return float
     */
    private function calculateCartTotal(Cart $cart)
    {
        $total = 0;
        
        foreach ($cart->items as $item) {
            $currentPrice = $item->product->sale_price;
            $total += $currentPrice * $item->quantity;
        }
        
        return $total;
    }


    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
    */
    public function update(Request $request, string $id)
    {
        // Validate dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Tìm mục giỏ hàng
            $cartItem = CartItem::findOrFail($id);
            
            // Kiểm tra xem mục giỏ hàng có thuộc về người dùng hiện tại không
            $cart = Cart::where('user_id', Auth::id())->firstOrFail();
            if ($cartItem->cart_id !== $cart->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền cập nhật mục này trong giỏ hàng'
                ], 403);
            }
            
            // Lấy thông tin sản phẩm để kiểm tra tồn kho
            $product = Product::findOrFail($cartItem->product_id);
            
            // Kiểm tra số lượng tồn kho
            if ($product->stock_quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số lượng sản phẩm không đủ trong kho',
                    'available_quantity' => $product->stock_quantity
                ], 422);
            }
            
            // Cập nhật số lượng
            $cartItem->update([
                'quantity' => $request->quantity
            ]);
            
            // Tải lại giỏ hàng với dữ liệu mới
            $cart = $cart->fresh(['items', 'items.product', 'items.product.images']);
            
            // Định dạng dữ liệu giỏ hàng
            $formattedCart = $this->formatCartData($cart);
            
            return response()->json([
                'success' => true,
                'message' => 'Giỏ hàng đã được cập nhật',
                'data' => $formattedCart
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi cập nhật giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cartItem = CartItem::findOrFail($id);
        $cart = Cart::where('user_id', Auth::id())->firstOrFail();
        if($cartItem->cart_id !== $cart->id) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền xoá mục này từ giỏ hàng'
            ], 403);
        }

        $cartItem->delete();

        $cart = $cart->fresh(['items', 'items.product', 'items.product.images']);

        $formattedCart = $this->formatCartData($cart);

        return response()->json([
            'success' => true,
            'message' => 'Sản phẩm đã được xóa khỏi giỏ hàng',
            'data' => $formattedCart
        ]);
    }

    public function countItems() {
        try {
            $cart = Cart::where('user_id', Auth::id())->first();

            // Nếu không có giỏ hàng, trả về số lượng 0
            if (!$cart) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'item_count' => 0
                    ]
                ], 200);
            }

            $itemCount = $cart->items()->sum('quantity');

            return response()->json([
                'success' => true,
                'data' => [
                    'item_count' => $itemCount
                ]
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi lấy tổng số sản phẩm trong giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
