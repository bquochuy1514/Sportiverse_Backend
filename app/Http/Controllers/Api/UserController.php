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
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role if provided
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $users = $query->get();

        $users->each(function ($user) {
            $user->avatar = str_starts_with($user->avatar, 'http') ? $user->avatar : url('storage/' . $user->avatar);
        });

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách người dùng thành công',
            'users' => $users
        ], 200);
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

    
    public function updateRole(Request $request, $id)
    {
        // Validate the request
        $request->validate([
            'role' => 'required|string|in:admin,customer',
        ]);

        // Get the authenticated user
        $authUser = Auth::user();

        // Find the user to update
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại.'
            ], 404);
        }

        // Prevent admin from updating their own role
        if ($user->id === $authUser->id) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể cập nhật vai trò của chính bạn.'
            ], 403);
        }

        // Update the user's role
        $user->role = $request->role;
        $user->save();

        // Transform avatar URL for consistency
        $user->avatar = str_starts_with($user->avatar, 'http') 
            ? $user->avatar 
            : url('storage/' . $user->avatar);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật vai trò người dùng thành công.',
            'user' => $user
        ], 200);
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

    public function destroy($id)
    {
        // Find the user to delete
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại.'
            ], 404);
        }

        // Prevent admin from deleting their own account
        if ($user->role == 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xóa tài khoản của admin.'
            ], 403);
        }

        // Delete the user's avatar if it's not a default avatar
        if ($user->avatar) {
            $defaultAvatars = ['avatars/default.jpg', 'avatars/admin.jpg'];
            $isDefaultAvatar = false;

            foreach ($defaultAvatars as $defaultAvatar) {
                if (str_ends_with($user->avatar, $defaultAvatar)) {
                    $isDefaultAvatar = true;
                    break;
                }
            }

            if (!$isDefaultAvatar) {
                Storage::disk('public')->delete($user->avatar);
            }
        }

        // Delete the user
        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa người dùng thành công.'
        ], 200);
    }
}
