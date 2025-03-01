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
    public function redirect()
    {
        return $this->googleAuthService->redirectToGoogle();
    }

    /**
     * Xử lý callback từ Google
     */
    public function callback()
    {
        $result = $this->googleAuthService->handleGoogleCallback();

        return redirect($result['redirect']);
    }

    /**
     * Liên kết tài khoản Google với tài khoản hiện tại
     */
    public function linkGoogleAccount()
    {
        $result = $this->googleAuthService->linkGoogleAccount();

        return redirect($result['redirect']);
    }
}
