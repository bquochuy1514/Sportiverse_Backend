<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();

        $users->each(function ($user) {
            $user->avatar = $user->avatar ? url('storage/' . $user->avatar) : url('storage/avatars/default.jpg');
        });

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách người dùng thành công',
            'users' => $users
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       
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
    public function update(Request $request)
    {
        $fields = $request->validate([
            'name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'phone' => 'nullable|string|max:11',
            'address' => 'nullable|string'
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            // Lưu thông tin avatar hiện tại
            $currentAvatar = $user->avatar;

            // Lưu avatar mới
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $fields['avatar'] = $avatarPath;

            // Thử xóa avatar cũ nếu không phải là avatar mặc định
            if ($currentAvatar) {
                $defaultAvatars = ['avatars/default.jpg', 'avatars/admin.jpg'];
                
                // Kiểm tra xem avatar hiện tại có phải là mặc định không
                $isDefaultAvatar = false;
                foreach ($defaultAvatars as $defaultAvatar) {
                    if (str_ends_with($currentAvatar, $defaultAvatar)) {
                        $isDefaultAvatar = true;
                        break;
                    }
                }
                
                // Nếu không phải avatar mặc định thì xóa
                if (!$isDefaultAvatar) {
                    Storage::disk('public')->delete($currentAvatar);
                }
            }
        }

        $user->update($fields);

        // Cập nhật đường dẫn avatar với domain
        $user->avatar = $user->avatar ? url('storage/' . $user->avatar) : url('storage/avatars/default.jpg');

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật thông tin thành công',
            'user' => $user
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed'
        ]);
        $user = Auth::user();
        // Kiểm tra mật khẩu cũ
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu cũ không đúng.',
            ], 400);
        }
        
        // Cập nhật mật khẩu mới
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công.',
        ], 200);
    }
}
