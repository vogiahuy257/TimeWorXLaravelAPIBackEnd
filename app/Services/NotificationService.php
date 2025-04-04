<?php
namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Validator;

class NotificationService
{
    /**
     * Tạo thông báo mới.
     */
    public function createNotification($data)
    {
        $validator = Validator::make($data, [
            'user_id' => 'required|uuid|exists:users,id',
            'notification_type' => 'required|string|max:50',
            'message' => 'required|string',
            'link' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        // Tạo thông báo mới
        return Notification::create([
            'user_id' => $data['user_id'],
            'notification_type' => $data['notification_type'],
            'message' => $data['message'],
            'link' => $data['link'] ?? null,
            'notification_date' => now(), 
        ]);
    }

    /**
     * Đánh dấu thông báo là đã đọc.
     */
    public function markAsRead($notificationIds,$userId)
    {
        return Notification::where('user_id', $userId)
        ->whereIn('id', $notificationIds)
        ->update(['read_status' => true]);
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc.
     */
    public function markAllAsRead($userId)
    {
        return Notification::where('user_id', $userId)
            ->update(['read_status' => true]);
    }

    /**
     * Xóa thông báo.
     */
    public function deleteNotification($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return null; // Nếu không tìm thấy thông báo
        }

        $notification->delete();
        return $notification;
    }

    /**
     * Xóa tất cả thông báo của người dùng.
     */
    public function deleteAllNotifications($userId)
    {
        return Notification::where('user_id', $userId)->delete();
    }

    /**
     * Lấy danh sách thông báo của người dùng.
     */
    public function getUserNotifications($userId)
    {
        return Notification::where('user_id', $userId)
            ->orderByRaw('read_status ASC')  // Sắp xếp chưa đọc lên đầu
            ->orderBy('notification_date', 'desc') // Sắp xếp theo ngày giảm dần
            ->get();
    }

}
