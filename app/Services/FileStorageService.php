<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileStorageService
{
    protected $storagePath;

    public function __construct($storagePath = 'documents')
    {
        $this->storagePath = $storagePath;
    }
    public function storeFile($file)
    {
        $this->ensureStoragePathExists();
        
        try {
            $storedPath = $file->store($this->storagePath, 'public');

            if (!Storage::disk('public')->exists($storedPath)) {
                throw new \Exception("File not saved correctly at path: $storedPath");
            }

            return $storedPath;
        } catch (\Exception $e) {
            Log::error("File upload failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }


    public function getFileUrl($filePath)
    {
        // Kiểm tra nếu file tồn tại trong disk 'public'
        if (Storage::disk('public')->exists($filePath)) {
            return asset('storage/' . $filePath);
        }

        return null; // Trả về null nếu file không tồn tại
    }

    public function setStoragePath($storagePath)
    {
        $this->storagePath = $storagePath;
        $this->ensureStoragePathExists(); // Đảm bảo thư mục tồn tại khi thay đổi đường dẫn
    }

    public function deleteFile($filePath)
    {
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        }

        Log::warning("File not found for deletion: $filePath");
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
