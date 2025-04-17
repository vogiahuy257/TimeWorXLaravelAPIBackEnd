<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    // Kiểm tra và đảm bảo thư mục lưu trữ tồn tại
    $this->ensureStoragePathExists();

    try {
        // Xóa ảnh cũ nếu có
        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            // Log việc xóa ảnh cũ
            Log::info("Deleting old profile picture: " . $user->profile_picture);
            Storage::disk('public')->delete($user->profile_picture);
        }

        // Kiểm tra xem file ảnh có tồn tại trong request không
        if (!$request->hasFile('profile_picture') || !$request->file('profile_picture')->isValid()) {
            return ['error' => 'Invalid or missing profile picture', 'status' => 400];
        }

        // Lưu ảnh mới vào thư mục 'profilePictures'
        $file = $request->file('profile_picture');
        $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension(); // Tạo tên file ngẫu nhiên
        $storedPath = $file->storeAs($this->storagePath, $fileName, 'public');

        // Kiểm tra và đảm bảo ảnh được lưu đúng
        if (!Storage::disk('public')->exists($storedPath)) {
            throw new \Exception("File not saved correctly at path: $storedPath");
        }
        // Cập nhật đường dẫn ảnh mới vào database
        $user->profile_picture = url('storage/' . $storedPath);
        $user->save();

        // Trả về URL ảnh mới (bao gồm đường dẫn tuyệt đối)
        return [
            'message' => 'Profile picture updated successfully',
            'profile_picture' => $user->profile_picture, // Sử dụng url() để tạo đường dẫn tuyệt đối
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
        // Sử dụng url() để trả về đường dẫn tuyệt đối
        return Storage::disk('public')->exists($filePath) ? url('storage/' . $filePath) : null;
    }


    /**
     * Xóa ảnh đại diện
     */
    public function deleteProfilePicture($filePath)
    {
        if (Storage::disk('public')->exists($filePath)) {
            // Log việc xóa ảnh
            Log::info("Deleting profile picture: $filePath");
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
            // Tạo thư mục nếu chưa tồn tại
            Storage::disk('public')->makeDirectory($this->storagePath);
            Log::info("Storage directory created: " . $this->storagePath);
        }
    }
}
