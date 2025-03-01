<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\SettingRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Services\ProfilePictureService;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    protected $settingRepo,$profilePictureService;

    public function __construct(SettingRepository $settingRepo,ProfilePictureService $profilePictureService)
    {
        $this->settingRepo = $settingRepo;
        $this->profilePictureService = $profilePictureService;
    }

    /**
     * Lấy thông tin cài đặt của user
     */
    public function show()
    {
        $setting = $this->settingRepo->getUserSetting();

        if (!$setting) {
            return redirect(env('FRONTEND_URL') . '/login?status=You-are-not-logged-in.');
        }

        return response()->json($setting->only(['id', 'language', 'color_system']));
    }

    /**
     * Cập nhật setting của user
     */
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'language' => 'nullable|string|max:10',
            'color_system' => 'nullable|string|max:50',
        ]);

        $setting = $this->settingRepo->updateUserSetting($validatedData);

        if (!$setting) {
            return redirect(env('FRONTEND_URL') . '/login?status=You-are-not-logged-in.');
        }

        return response()->json([
            'message' => 'Setting updated successfully',
            'data' => $setting->only(['id', 'language', 'color_system']),
        ]);
    }

    //------------------------ USER ----------------------

    // XỬ LÝ cho thay đổi setting user name
    public function updateUserName(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user->name = $request->input('name');
        $user->save();

        return response()->json(['message' => 'User name updated successfully', 'user' => $user]);
    }

    // Xử lý thay đổi cho setting user email
    public function updateUserEmail(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email',
        ]);

        $user->email = $request->input('email');
        $user->email_verified_at = null;
        $user->save();

        return response()->json(['message' => 'User email updated successfully', 'user' => $user]);
    }

    // Xử lý thay đổi hình ảnh của user
    public function updateUserProfilePicture(Request $request)
    {
        $result = $this->profilePictureService->updateProfilePicture($request);

        return response()->json([
            'message' => $result['message'] ?? 'Error',
            'profile_picture' => $result['profile_picture'] ?? null
        ], $result['status']);
    }

    // Xử lý thay đổi mật khẩu cho user
    public function updateUserPassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);
    }

    // Xử lý xóa tài khoản cho User
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(env('FRONTEND_URL') . '/login');
    }
}
