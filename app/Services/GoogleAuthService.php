<?php

namespace App\Services;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class GoogleAuthService
{
    /**
     * Chuyển hướng người dùng đến Google để xác thực
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Xử lý đăng nhập hoặc đăng ký qua Google
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Kiểm tra nếu user đã đăng nhập bằng Google trước đó
            $user = User::where('google_id', $googleUser->getId())->first();
            if ($user) {
                Auth::login($user);
                return ['redirect' => env('FRONTEND_URL') . '/dashboard/home'];
            }

            // Kiểm tra email có tồn tại nhưng chưa liên kết Google
            $user = User::where('email', $googleUser->getEmail())->first();
            if ($user && !$user->google_id) {
                return ['redirect' => env('FRONTEND_URL') . '/login?error=email-exists'];
            }

            // Nếu chưa có tài khoản, tạo mới
            if (!$user) {
                $user = User::create([
                    'name'              => $googleUser->getName(),
                    'email'             => $googleUser->getEmail(),
                    'password'          => Hash::make(uniqid()), // Mật khẩu ngẫu nhiên
                    'google_id'         => $googleUser->getId(),
                    'email_verified_at' => now(),
                    'profile_picture'   => $googleUser->getAvatar(),
                    'role'              => 'User'
                ]);
            }

            Auth::login($user);
            return ['redirect' => env('FRONTEND_URL') . '/dashboard/home'];

        } catch (\Exception $e) {
            return ['redirect' => env('FRONTEND_URL') . '/login?error=email-exists'];
        }
    }

    /**
     * Liên kết tài khoản Google với tài khoản hiện tại
     */
    public function linkGoogleAccount()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = Auth::user();

            if (!$user) {
                return ['redirect' => env('FRONTEND_URL') . '/login?error=not-authenticated'];
            }

            // Kiểm tra nếu Google ID đã liên kết với tài khoản khác
            $existingGoogleUser = User::where('google_id', $googleUser->getId())->first();
            if ($existingGoogleUser && $existingGoogleUser->id !== $user->id) {
                return ['redirect' => env('FRONTEND_URL') . '/settings/user?error=google-linked'];
            }

            // Kiểm tra email có khớp không
            if ($user->email !== $googleUser->getEmail()) {
                return ['redirect' => env('FRONTEND_URL') . '/settings/user?error=email-mismatch'];
            }

            // Cập nhật tài khoản
            $user->google_id = $googleUser->getId();
            if (!$user->profile_picture || !Storage::disk('public')->exists($user->profile_picture)) {
                $user->profile_picture = $googleUser->getAvatar();
            }
            $user->save();

            return ['redirect' => env('FRONTEND_URL') . '/settings/user?success=google-linked'];

        } catch (\Exception $e) {
            return ['redirect' => env('FRONTEND_URL') . '/settings/user?error=google-failed'];
        }
    }
}
