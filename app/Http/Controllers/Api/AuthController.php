<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    /**
     * Đăng ký người dùng mới
     */
    public function register(Request $request)
    {
        try {
            $fields = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
            ]);

            $fields['avatar'] = url('/storage/avatars/default.jpg');

            $user = User::create($fields);

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công, vui lòng đăng nhập',
                'data' => [
                    'user' => $user,
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực dữ liệu',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Đăng nhập người dùng
     */
    public function login(Request $request)
    {
        try {
            $fields = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $request->email)->first();

            if(!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => "Email hoặc mật khẩu không chính xác"
                ], 401);
            }

            $token = $user->createToken($user->name);

            return response()->json([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'user' => $user,
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực dữ liệu',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Đăng xuất người dùng
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công'
        ]);
    }

    /**
     * Lấy thông tin người dùng đang đăng nhập
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()
            ]
        ]);
    }

    /**
     * Chuyển hướng người dùng đến trang đăng nhập Google
     */
    public function redirectToGoogle()
    {
        $client = new \GuzzleHttp\Client(['verify' => false]);
    
        return response()->json([
            'success' => true,
            'url' => Socialite::driver('google')
                    ->setHttpClient($client)
                    ->stateless()
                    ->redirect()
                    ->getTargetUrl()
        ]);
    }

    /**
     * Xử lý callback từ Google sau khi xác thực
     */
    public function handleGoogleCallback()
    {
        try {
            // Tạo instance của Guzzle Client với tùy chọn tắt xác minh SSL
            $client = new \GuzzleHttp\Client(['verify' => false]);
            
            // Cấu hình Socialite để sử dụng client tùy chỉnh
            $googleUser = Socialite::driver('google')
                ->setHttpClient($client)
                ->stateless()
                ->user();
                
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Bắt đầu transaction để đảm bảo tính nhất quán dữ liệu
            DB::beginTransaction();
            
            // Kiểm tra xem tài khoản Google đã liên kết với người dùng nào chưa
            $socialAccount = SocialAccount::where('provider_name', 'google')
                ->where('provider_id', $googleUser->id)
                ->first();
                
            if ($socialAccount) {
                // Nếu đã có tài khoản liên kết, lấy thông tin người dùng
                $user = $socialAccount->user;
                
                // Cập nhật thông tin avatar nếu cần
                if ($googleUser->avatar && !$user->avatar) {
                    $user->avatar = $googleUser->avatar;
                    $user->save();
                }
            } else {
                // Kiểm tra xem email đã tồn tại trong hệ thống chưa
                $user = User::where('email', $googleUser->email)->first();
                
                if (!$user) {
                    // Tạo người dùng mới nếu chưa tồn tại
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'avatar' => $googleUser->avatar ?? url('/storage/avatars/default.jpg'),
                        'role' => 'customer', // Mặc định là khách hàng
                        'email_verified_at' => now(), // Email đã được xác thực qua Google
                    ]);
                }
                
                // Tạo liên kết với tài khoản mạng xã hội
                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider_id' => $googleUser->id,
                    'provider_name' => 'google'
                ]);
            }
            
            // Tạo token cho người dùng
            $token = $user->createToken($user->name);
            
            DB::commit();
            
            // Trả về thông tin người dùng và token
            return response()->json([
                'success' => true,
                'message' => 'Đăng nhập Google thành công',
                'data' => [
                    'user' => $user,
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Đăng nhập Google thất bại: ' . $e->getMessage()
            ], 500);
        }
    }
}
