<?php

namespace App\Http\Controllers\Api;

use App\Models\ReportComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Models\Task;
class ReportCommentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    // Xem danh sách bình luận của một báo cáo
    public function index($taskId, $userId)
    {
        try {
            // Lấy bình luận được ghim kèm thông tin người dùng
            $pinnedComments = ReportComment::with(['user' => function($query) {
                // Chỉ lấy các trường cần thiết từ bảng user
                $query->select('id', 'name', 'profile_picture');
            }])
            ->where('task_id', $taskId) // Lọc theo ID task
            ->where('is_pinned', true) // Chỉ lấy bình luận được ghim
            ->get();

            // Lấy các bình luận thông thường kèm thông tin người dùng
            $regularComments = ReportComment::with(['user' => function($query) {
                // Chỉ lấy các trường cần thiết từ bảng user
                $query->select('id', 'name', 'profile_picture');
            }])
            ->where('task_id', $taskId) // Lọc theo ID task
            ->get();

            // Trả về danh sách bình luận dạng JSON
            return response()->json([
                'pinned_comments' => $pinnedComments, // Bình luận được ghim
                'regular_comments' => $regularComments // Bình luận thường
            ]);
        } catch (\Exception $e) {
            // Xử lý lỗi và trả về thông báo lỗi
            return response()->json(['error' => 'Failed to retrieve comments', 'message' => $e->getMessage()], 500);
        }
    }

    // Thêm bình luận vào báo cáo
    public function store(Request $request, $taskId, $userId)
    {
        try {
            $validatedData = $request->validate([
                'comment' => 'required|string', // Bình luận là chuỗi và bắt buộc
                'is_project_manager' => 'required|boolean', // Xác định vai trò của người dùng
            ]);

            // Tạo mới một bình luận
            $comment = new ReportComment();
            $comment->task_id = $taskId; // Gắn bình luận với task cụ thể
            $comment->comment_by_user_id = $userId; // ID người dùng tạo bình luận
            $comment->comment = $validatedData['comment']; // Nội dung bình luận
            $comment->is_project_manager = $validatedData['is_project_manager']; // Vai trò người dùng
            $comment->is_pinned = false; // Mặc định không ghim
            $comment->save(); // Lưu bình luận vào database


            if ($request->is_project_manager) {
                $task = Task::with('users')->findOrFail($taskId);
                $message = "New comment on report to task: '{$task->task_name}'";
                // Nếu là PM: gửi cho tất cả users của task, trừ chính mình
                foreach ($task->users as $user) {
                        $this->notificationService->sendNotification(
                            $user->getKey(), // an toàn hơn so với $user->id nếu dùng UUID
                            'info',
                            $message,
                            '/dashboard/task'
                        );
                }
            } else {
                
                $task = Task::findOrFail($taskId);
                $message = "New comment on report to task: '{$task->task_name}'";
                // Nếu là user: gửi cho người phụ trách task
                if ($task->in_charge_user_id && $task->in_charge_user_id !== $userId) {
                    $this->notificationService->sendNotification(
                        $task->in_charge_user_id,
                        'info',
                        $message,
                        '/dashboard/project/' . $task->project_id . '/broad'
                    );
                }
            }

            // Lấy lại danh sách bình luận để trả về
            $comment = ReportComment::with(['user' => function($query) {
                // Chỉ lấy các trường cần thiết từ bảng user
                $query->select('id', 'name', 'profile_picture');
            }])
            ->where('task_id', $taskId) // Lọc theo ID task
            ->get();

            // Trả về danh sách bình luận dạng JSON
            return response()->json($comment);
        } catch (\Exception $e) {
            // Xử lý lỗi và trả về thông báo lỗi
            return response()->json(['error' => 'Failed to create comment', 'message' => $e->getMessage()], 500);
        }
    }

    // Xóa bình luận của báo cáo
    public function destroy($commentId, $userId)
    {
        try {
            // Tìm bình luận theo ID
            $comment = ReportComment::findOrFail($commentId);

            // Kiểm tra quyền xóa bình luận
            if ($comment->comment_by_user_id !== $userId && !request()->user()->is_project_manager) {
                return response()->json(['message' => 'Unauthorized'], 403); // Không có quyền
            }

            // Xóa bình luận
            $comment->delete();

            // Trả về thông báo thành công
            return response()->json(['message' => 'Comment deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Xử lý lỗi khi không tìm thấy bình luận
            return response()->json(['error' => 'Comment not found', 'message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            // Xử lý lỗi khác và trả về thông báo lỗi
            return response()->json(['error' => 'Failed to delete comment', 'message' => $e->getMessage()], 500);
        }
    }

    // Ghim bình luận
    public function pinComment($commentId)
    {
        try {
            // Tìm bình luận theo ID
            $comment = ReportComment::findOrFail($commentId);
            $comment->is_pinned = true; // Đánh dấu là đã ghim
            $comment->save(); // Lưu lại thay đổi

            // Trả về bình luận đã được ghim
            return response()->json($comment);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Xử lý lỗi khi không tìm thấy bình luận
            return response()->json(['error' => 'Comment not found', 'message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            // Xử lý lỗi khác và trả về thông báo lỗi
            return response()->json(['error' => 'Failed to pin comment', 'message' => $e->getMessage()], 500);
        }
    }

    // Gỡ ghim bình luận
    public function unpinComment($commentId)
    {
        try {
            // Tìm bình luận theo ID
            $comment = ReportComment::findOrFail($commentId);
            $comment->is_pinned = false; // Bỏ đánh dấu ghim
            $comment->save(); // Lưu lại thay đổi

            // Trả về bình luận đã được gỡ ghim
            return response()->json($comment);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Xử lý lỗi khi không tìm thấy bình luận
            return response()->json(['error' => 'Comment not found', 'message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            // Xử lý lỗi khác và trả về thông báo lỗi
            return response()->json(['error' => 'Failed to unpin comment', 'message' => $e->getMessage()], 500);
        }
    }
}
