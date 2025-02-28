<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\SettingRepository;

class SettingController extends Controller
{
    protected $settingRepo;

    public function __construct(SettingRepository $settingRepo)
    {
        $this->settingRepo = $settingRepo;
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
}
