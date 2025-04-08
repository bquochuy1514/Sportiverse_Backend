<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function googleLogin()
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

    public function googleAuthentication(Request $request)
    {
        try {
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $googleUser = Socialite::driver('google')
                ->setHttpClient($client)
                ->stateless()
                ->user();

            DB::beginTransaction();

            $socialAccount = SocialAccount::where('provider_name', 'google')
                ->where('provider_id', $googleUser->id)
                ->first();

            if ($socialAccount) {
                $user = $socialAccount->user;
                // Chỉ cập nhật avatar nếu avatar hiện tại không phải là avatar tùy chỉnh (không chứa /storage/avatars/)
                if ($googleUser->avatar && !str_contains($user->avatar, '/storage/avatars/') && $user->avatar !== $googleUser->avatar) {
                    $user->avatar = $googleUser->avatar;
                    $user->save();
                }
            } else {
                $user = User::where('email', $googleUser->email)->first();

                if ($user) {
                    SocialAccount::create([
                        'user_id' => $user->id,
                        'provider_id' => $googleUser->id,
                        'provider_name' => 'google',
                    ]);
                    // Chỉ cập nhật avatar nếu avatar hiện tại không phải là avatar tùy chỉnh
                    if ($googleUser->avatar && !str_contains($user->avatar, '/storage/avatars/') && $user->avatar !== $googleUser->avatar) {
                        $user->avatar = $googleUser->avatar;
                        $user->save();
                    }
                } else {
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'avatar' => $googleUser->avatar ?? '/storage/avatars/default.jpg',
                        'role' => 'customer',
                        'email_verified_at' => now(),
                    ]);

                    SocialAccount::create([
                        'user_id' => $user->id,
                        'provider_id' => $googleUser->id,
                        'provider_name' => 'google',
                    ]);
                }
            }

            $token = $user->createToken('Google Login')->plainTextToken;

            DB::commit();

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173') . '/auth/callback';
            return redirect()->away("{$frontendUrl}?token={$token}&id={$user->id}&name=" . urlencode($user->name) . "&email={$user->email}&avatar=" . urlencode($user->avatar) . "&role={$user->role}");
        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Đăng nhập Google thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Chuyển hướng người dùng đến trang đăng nhập Facebook
    */
    public function facebookLogin()
    {
        $client = new \GuzzleHttp\Client(['verify' => false]);
        return response()->json([
            'success' => true,
            'url' => Socialite::driver('facebook')
                ->setHttpClient($client)
                ->stateless()
                ->scopes(['email', 'public_profile']) // Đảm bảo lấy public_profile để có avatar
                ->redirect()
                ->getTargetUrl()
        ]);
    }

    // Facebook Authentication
    public function facebookAuthentication(Request $request)
    {
        try {
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $facebookUser = Socialite::driver('facebook')
                ->setHttpClient($client)
                ->stateless()
                ->user();

            $token = $facebookUser->token;
            $response = $client->get("https://graph.facebook.com/v19.0/me?fields=picture&access_token={$token}");
            $json = json_decode($response->getBody(), true);
            $pictureUrl = $json['picture']['data']['url'];

            DB::beginTransaction();

            $socialAccount = SocialAccount::where('provider_name', 'facebook')
                ->where('provider_id', $facebookUser->id)
                ->first();

            if ($socialAccount) {
                $user = $socialAccount->user;
                // Chỉ cập nhật avatar nếu avatar hiện tại không phải là avatar tùy chỉnh
                if (!str_contains($user->avatar, '/storage/avatars/') && $user->avatar != $pictureUrl) {
                    $user->avatar = $pictureUrl;
                    $user->save();
                }
            } else {
                $user = User::where('email', $facebookUser->email)->first();

                if ($user) {
                    SocialAccount::create([
                        'user_id' => $user->id,
                        'provider_id' => $facebookUser->id,
                        'provider_name' => 'facebook',
                    ]);
                    // Chỉ cập nhật avatar nếu avatar hiện tại không phải là avatar tùy chỉnh
                    if (!str_contains($user->avatar, '/storage/avatars/') && $user->avatar != $pictureUrl) {
                        $user->avatar = $pictureUrl;
                        $user->save();
                    }
                } else {
                    $user = User::create([
                        'name' => $facebookUser->name,
                        'email' => $facebookUser->email ?? 'facebook_' . $facebookUser->id . '@example.com',
                        'avatar' => $pictureUrl,
                        'role' => 'customer',
                        'email_verified_at' => now(),
                    ]);

                    SocialAccount::create([
                        'user_id' => $user->id,
                        'provider_id' => $facebookUser->id,
                        'provider_name' => 'facebook',
                    ]);
                }
            }

            $token = $user->createToken('Facebook Login')->plainTextToken;

            DB::commit();

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173') . '/auth/callback';
            return redirect()->away("{$frontendUrl}?token={$token}&id={$user->id}&name=" . urlencode($user->name) . "&email={$user->email}&avatar=" . urlencode($user->avatar) . "&role={$user->role}");
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Đăng nhập Facebook thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

}