<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Lấy danh sách thông báo của người dùng.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $notifications = $this->notificationService->getUserNotifications($userId);

        return response()->json($notifications, 200);
    }

    /**
     * Đánh dấu thông báo là đã đọc.
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);
    
        $userId = $request->user()->id;
        $notificationIds = $request->input('notification_ids');
        $this->notificationService->markAsRead($notificationIds,$userId);

        return response()->json(['message' => 'Thông báo đã được đánh dấu là đã đọc'], 200);
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc.
     */
    public function markAllAsRead(Request $request)
    {
        $userId = $request->user()->id;

        $this->notificationService->markAllAsRead($userId);

        return response()->json(['message' => 'Tất cả thông báo đã được đánh dấu là đã đọc'], 200);
    }

    /**
     * Xóa thông báo.
     */
    public function destroy($id)
    {
        $notification = $this->notificationService->deleteNotification($id);

        if (!$notification) {
            return response()->json(['message' => 'Thông báo không tồn tại'], 404);
        }

        return response()->json(['message' => 'Thông báo đã được xóa'], 200);
    }

    /**
     * Xóa tất cả thông báo.
     */
    public function destroyAll(Request $request)
    {
        $userId = $request->user()->id;

        $this->notificationService->deleteAllNotifications($userId);

        return response()->json(['message' => 'Tất cả thông báo đã được xóa'], 200);
    }

    /**
     * Tạo thông báo mới.
     */
    public function store(Request $request)
    {
        $notification = $this->notificationService->createNotification($request->all());

        if (isset($notification['errors'])) {
            return response()->json(['errors' => $notification['errors']], 422);
        }

        return response()->json(['message' => 'Thông báo đã được tạo', 'notification' => $notification], 201);
    }
}
