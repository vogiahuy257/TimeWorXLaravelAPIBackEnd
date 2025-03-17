<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ReportZipper
{
    protected string $storageDisk; // Tên disk (local, s3, etc.)
    protected string $storagePath; // Đường dẫn thư mục lưu file ZIP

    /**
     * Constructor
     * 
     * @param string $storageDisk
     * @param string $storagePath
     */
    public function __construct(string $storageDisk = 'public', string $storagePath = 'zipped_reports')
    {
        $this->storageDisk = $storageDisk;
        $this->storagePath = $storagePath;
    }

    /**
     * Tạo file ZIP từ danh sách file báo cáo
     * 
     * @param string $zipFileName Tên file ZIP (e.g., 'project_reports.zip')
     * @param array $files Danh sách file (['file_path' => 'Tên file trong ZIP'])
     * @return string Đường dẫn đầy đủ tới file ZIP
     * 
     * @throws \Exception
     */
    public function createZip(string $zipFileName, array $files): string
    {
        // Đảm bảo thư mục tồn tại
        if (!Storage::disk($this->storageDisk)->exists($this->storagePath)) {
            Storage::disk($this->storageDisk)->makeDirectory($this->storagePath);
        }
        
        // Đường dẫn lưu file ZIP
        $zipPath = Storage::disk($this->storageDisk)->path("{$this->storagePath}/{$zipFileName}");


        // Mở file ZIP
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($files as $filePath => $fileNameInZip) {
                if (Storage::disk($this->storageDisk)->exists($filePath)) {
                    $zip->addFile(Storage::disk($this->storageDisk)->path($filePath), $fileNameInZip);
                } else {
                    \Log::warning("File not found: {$filePath}");
                }
            }
            $zip->close();
        } else {
            throw new \Exception("Could not create ZIP file: {$zipFileName}");
        }

        return $zipPath;
    }

    /**
     * Xóa file ZIP khỏi storage
     * 
     * @param string $zipFileName
     * @return bool
     */
    public function deleteZip(string $zipFileName): bool
    {
        $zipPath = "{$this->storagePath}/{$zipFileName}";

        if (Storage::disk($this->storageDisk)->exists($zipPath)) {
            return Storage::disk($this->storageDisk)->delete($zipPath);
        }

        return false;
    }

    /**
     * Tải file ZIP từ storage và trả về response download.
     * 
     * @param string $zipFileName Tên file ZIP cần tải
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * 
     * @throws \Exception
     */
    public function downloadZip(string $zipFileName)
    {
        $zipPath = "{$this->storagePath}/{$zipFileName}";

        if (!Storage::disk($this->storageDisk)->exists($zipPath)) {
            throw new \Exception("ZIP file not found: {$zipFileName}");
        }

        return response()->download(Storage::disk($this->storageDisk)->path($zipPath), $zipFileName);
    }


}
