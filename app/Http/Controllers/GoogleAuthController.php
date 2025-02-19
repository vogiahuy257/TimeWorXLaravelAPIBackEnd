<?php

namespace App\Http\Controllers;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        try{
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Kiểm tra xem user có đăng nhập bằng Google trước đó không
            $user = User::where('google_id', $googleUser->getId())->first();
            if ($user)
            {
                Auth::login($user);
                return redirect(env('FRONTEND_URL') . '/dashboard/home');
            }

            // ✅ Thông báo email đã đăng ký
            $user = User::where('email', $googleUser->getEmail())->first();
            if ($user && !$user->google_id) {
                return redirect(env('FRONTEND_URL') . '/login?error=email-exists'); 
            }

            if(!$user)
            {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(uniqid()), // ✅ Tạo password ngẫu nhiên để tránh lộ dữ liệu
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                    'profile_picture' => $googleUser->getAvatar(),
                    'role' => 'User'
                ]);  
            }
            Auth::login($user);
            return redirect(env('FRONTEND_URL') . '/dashboard/home');
        }
        catch (\Exception $e){
            dd($e);
        }
    }
}
