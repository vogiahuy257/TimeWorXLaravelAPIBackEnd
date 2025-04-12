<?php
namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Validator;
use App\Data\Factories\NotificationDataFactory;
use App\Events\NotificationReceived;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
class NotificationService
{
    /**
     * Gửi một thông báo đến người dùng.
     *
     * @param string $userId UUID của người nhận
     * @param string $type Loại thông báo (info, success, warning, error)
     * @param string $message Nội dung thông báo
     * @param string|null $link Đường dẫn (nếu có)
     *
     * @return Notification|array|null
     */
    public function sendNotification(string $userId, string $type, string $message, ?string $link = null)
    {
        $data = NotificationDataFactory::make($userId, $type, $message, $link);
        broadcast(new NotificationReceived($data));

        return $this->createNotification($data->toArray());
    }

    public function sendNotificationTaskStatusToManager(Task $task, Project $project, string $statusKey)
    {
        $projectName = $project->project_name ?? 'Unknown Project';
        $projectId = $project->project_id;
        $projectManager = $project->project_manager;

        $type = $statusKey === 'verify' ? 'error' : 'success';
        $message = match ($statusKey) {
            'done' => "The task is marked as done in project: '{$projectName}'.",
            'verify' => "The task is ready for verification in project: '{$projectName}'.",
            default => null,
        };

        if (!$message) return; // Nếu không phải các status cần gửi thông báo, bỏ qua

        $link = '/dashboard/project/' . $projectId . '/broad';

        // Gửi cho người được giao
        $this->sendNotification($task->in_charge_user_id, $type, $message, $link);

        // Gửi cho người phụ trách (nếu khác người được giao)
        if ($task->in_charge_user_id !== $task->assigned_to_user_id) {
            $this->sendNotification($task->assigned_to_user_id, $type, $message, $link);
        }

        // Gửi cho quản lý dự án (nếu khác người được giao và người phụ trách)
        if ($task->in_charge_user_id !== $projectManager && $projectManager !== $task->assigned_to_user_id) {
            $this->sendNotification($projectManager, $type, $message, $link);
        }
    }

    public function sendNotificationTaskStatusToAll(Task $task, Project $project, string $statusKey, User $excludeUser)
    {
        $projectName = $project->project_name ?? 'Unknown Project';
        $projectId = $project->project_id;
        $taskName = $task->task_name ?? 'Unnamed Task';

        // Lấy tên người quản lý từ DB (nếu cần)
        $excludeUserId = $excludeUser->id ?? null;
        $managerName = $excludeUser ? $excludeUser->name : 'Manager';

        $type = $statusKey == 'done'
        ? 'success'
        : ($statusKey == 'verify' ? 'error' : 'info');
 // Loại thông báo (có thể thay đổi theo yêu cầu)
        $statusText = $statusKey; // Done, Verify,...
        $message = "Task '{$taskName}' in project '{$projectName}' has been updated to status '{$statusText}' by manager {$managerName}.";

        $link = '/dashboard/project/' . $projectId . '/broad';

        $excludedIds = [
            $task->in_charge_user_id,
            $task->assigned_to_user_id,
            $project->project_manager,
            $excludeUserId,
        ];

        // Gửi cho người được giao
        if ($task->in_charge_user_id !== $excludeUserId) {
            $this->sendNotification($task->in_charge_user_id, $type, $message, $link);
        }

        // Gửi cho người phụ trách (nếu khác)
        if (!in_array($task->assigned_to_user_id, $excludedIds)) {
            $this->sendNotification($task->assigned_to_user_id, $type, $message, $link);
        }

        // Gửi cho quản lý dự án (nếu khác người gửi)
        if (!in_array($project->project_manager, $excludedIds)) {
            $this->sendNotification($project->project_manager, $type, $message, $link);
        }

        $link = '/dashboard/task';
        // Gửi cho các user liên quan (qua pivot)
        foreach ($task->users as $user) {
            if (!in_array($user->id, $excludedIds)) {
                $this->sendNotification($user->id, $type, $message, $link);
            }
        }
    }




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
            'notification_date' => 'nullable|date',
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
            'notification_date' => $data['notification_date'] ?? now(), 
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
