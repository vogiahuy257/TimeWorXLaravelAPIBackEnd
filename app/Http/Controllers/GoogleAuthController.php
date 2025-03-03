<?php

namespace App\Http\Controllers;

use App\Services\GoogleAuthService;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    protected $googleAuthService;

    public function __construct(GoogleAuthService $googleAuthService)
    {
        $this->googleAuthService = $googleAuthService;
    }

    /**
     * Chuyển hướng đến Google để xác thực
     */
    public function redirect(Request $request)
    {
        $mode = $request->query('mode', 'login'); // Mặc định là login nếu không có mode
        return $this->googleAuthService->redirectToGoogle($mode);
    }

    /**
     * Xử lý callback từ Google (phân biệt đăng nhập và liên kết tài khoản)
     */
    public function callback(Request $request)
    {
        try {
            $result = $this->googleAuthService->handleGoogleCallback();
            return redirect($result['redirect']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error while authenticating Google account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
