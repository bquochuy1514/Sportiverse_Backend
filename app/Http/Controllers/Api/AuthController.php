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
                'password' => 'required|string|min:8|confirmed',
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
            $errors = $e->errors();
            $message = 'Lỗi xác thực dữ liệu';
            
            // Xử lý thông báo lỗi cụ thể cho từng trường hợp
            if (isset($errors['email'])) {
                foreach ($errors['email'] as $error) {
                    if (str_contains($error, 'has already been taken')) {
                        $message = 'Email đã tồn tại trong hệ thống';
                        break;
                    }
                }
            } elseif (isset($errors['password'])) {
                if (str_contains(implode(' ', $errors['password']), 'confirmation')) {
                    $message = 'Mật khẩu xác nhận không khớp';
                } elseif (str_contains(implode(' ', $errors['password']), 'at least')) {
                    $message = 'Mật khẩu phải có ít nhất 8 ký tự';
                }
            } elseif (isset($errors['name'])) {
                $message = 'Vui lòng nhập tên của bạn';
            }
            
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi trong quá trình đăng ký',
                'error' => $e->getMessage()
            ], 500);
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
                'remember' => 'boolean', // Thêm trường remember
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => "Email hoặc mật khẩu không chính xác"
                ], 401);
            }

            // Xác định thời hạn token dựa vào "remember me"
            $tokenExpiration = $request->remember ? now()->addMonths(6) : now()->addDay();
            
            // Xóa các token cũ nếu có
            $user->tokens()->delete();
            
            // Tạo token mới với thời hạn tương ứng
            $token = $user->createToken($user->name, ['*'], $tokenExpiration);

            // Chuẩn bị dữ liệu trả về
            $responseData = [
                'user' => $user,
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
            ];

            // Chỉ thêm expires_at nếu remember là true
            if ($request->remember) {
                $responseData['expires_at'] = $tokenExpiration->toDateTimeString();
            }

            return response()->json([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'data' => $responseData
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
}
