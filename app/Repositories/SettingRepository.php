<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SettingRepository
{
    /**
     * Lấy setting của user hiện tại, nếu chưa có thì tạo mới
     */
    public function getUserSetting()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return $user->setting()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'language' => 'en',
            'color_system' => 'light-mode',
            'screen_mode' => 'default'
        ]);
    }

    /**
     * Cập nhật setting của user hiện tại
     */
    public function updateUserSetting($data)
    {
        $setting = $this->getUserSetting();

        if (!$setting) {
            return null;
        }
        $setting->update($data);

        return $setting;
    }
}
