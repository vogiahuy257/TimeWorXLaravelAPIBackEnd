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
    public function redirectToGoogle($mode = 'login')
    {
        $state = json_encode(['mode' => $mode]); // Mã hóa mode vào state
        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Xử lý đăng nhập hoặc đăng ký qua Google
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $state = request()->input('state');
            $mode = 'login'; // Mặc định là đăng nhập nếu không có state

            if ($state) {
                $decodedState = json_decode(urldecode($state), true);
                $mode = $decodedState['mode'] ?? 'login';
            }

            if ($mode === 'link') {
                return $this->linkGoogleAccount($googleUser); // Truyền $googleUser vào
            } else {
                return $this->loginOrRegisterGoogleUser($googleUser);
            }
        } catch (\Exception $e) {
            return ['redirect' => env('FRONTEND_URL') . '/login?error=google-auth-failed'];
        }
    }

    private function loginOrRegisterGoogleUser($googleUser)
    {
        $user = User::where('google_id', $googleUser->getId())->first();
        if ($user) {
            Auth::login($user);
            return ['redirect' => env('FRONTEND_URL') . '/dashboard/home'];
        }

        $user = User::where('email', $googleUser->getEmail())->first();
        if ($user && !$user->google_id) {
            return ['redirect' => env('FRONTEND_URL') . '/login?error=email-exists'];
        }

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
    }

    /**
     * Liên kết tài khoản Google với tài khoản hiện tại
     */
    private function linkGoogleAccount($googleUser)
    {
        $user = Auth::user();
        if (!$user) {
            return ['redirect' => env('FRONTEND_URL') . '/login?error=not-authenticated'];
        }

        $existingGoogleUser = User::where('google_id', $googleUser->getId())->first();
        if ($existingGoogleUser && $existingGoogleUser->id !== $user->id) {
            return ['redirect' => env('FRONTEND_URL') . '/setting/user?error=google-linked'];
        }

        if ($user->email !== $googleUser->getEmail()) {
            return ['redirect' => env('FRONTEND_URL') . '/setting/user?error=email-mismatch'];
        }

        $user->google_id = $googleUser->getId();
        if (!$user->profile_picture || !Storage::disk('public')->exists($user->profile_picture)) {
            $user->profile_picture = $googleUser->getAvatar();
        }
        $user->save();

        return ['redirect' => env('FRONTEND_URL') . '/setting/user?success=google-linked'];
    }

}
