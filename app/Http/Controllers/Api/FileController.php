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
        // Lấy đường dẫn file từ query string
        $filePath = $request->query('path'); 

        // Kiểm tra file có tồn tại trong storage không
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Trả về file
        return Storage::download($filePath);
    }
}
