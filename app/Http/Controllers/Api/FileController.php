<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    public function downloadFile(Request $request)
    {
        $filePath = $request->query('path'); 

        // Kiểm tra đường dẫn có hợp lệ không
        if (strpos($filePath, '..') !== false) {
            return response()->json(['error' => 'Invalid file path'], 400);
        }
        
        $fullPath = storage_path("app/public/{$filePath}");

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download($fullPath);
    }

}
