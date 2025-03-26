<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ProfilePictureService
{
    protected $storagePath;

    public function __construct($storagePath = 'profilePictures')
    {
        $this->storagePath = $storagePath;
    }

    /**
     * Cập nhật ảnh đại diện của người dùng
     */
    public function updateProfilePicture(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return ['error' => 'Unauthorized', 'status' => 401];
        }

        // $request->validate([
        //     'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        // ]);

        $this->ensureStoragePathExists();

        try {
            // Xóa ảnh cũ nếu có
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Lưu ảnh mới
            $storedPath = $request->file('profile_picture')->store($this->storagePath, 'public');

            if (!Storage::disk('public')->exists($storedPath)) {
                throw new \Exception("File not saved correctly at path: $storedPath");
            }

            // Cập nhật đường dẫn mới vào database
            $user->profile_picture = asset('storage/' . $storedPath);
            $user->save();

            return [
                'message' => 'Profile picture updated successfully',
                'profile_picture' => asset('storage/' . $storedPath),
                'status' => 200
            ];
        } catch (\Exception $e) {
            Log::error("Profile picture upload failed", ['error' => $e->getMessage()]);
            return ['error' => 'Failed to upload profile picture', 'status' => 500];
        }
    }

    /**
     * Lấy URL ảnh đại diện của người dùng
     */
    public function getProfilePictureUrl($filePath)
    {
        return Storage::disk('public')->exists($filePath) ? asset('storage/' . $filePath) : null;
    }

    /**
     * Xóa ảnh đại diện
     */
    public function deleteProfilePicture($filePath)
    {
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        }

        Log::warning("Profile picture not found for deletion: $filePath");
        return false;
    }

    /**
     * Đảm bảo thư mục lưu trữ tồn tại
     */
    protected function ensureStoragePathExists()
    {
        if (!Storage::disk('public')->exists($this->storagePath)) {
            Storage::disk('public')->makeDirectory($this->storagePath);
        }
    }
}
