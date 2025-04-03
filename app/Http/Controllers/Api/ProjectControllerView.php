<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class ProjectControllerView extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
    }


    public function show($id)
    {
        $project = Project::findOrFail($id);

        $tasks = Task::where('project_id', $id)->with('users')->get();

        $response = [
            'project' => [
                'id' => $project->project_id, 
                'name' => $project->project_name, 
                'description' => $project->project_description, 
                'deadline' => $project->end_date,
                'user_count' => $project->countProjectUsers(),
            ],
            'tasks' => [
                'to-do' => [],
                'in-progress' => [],
                'verify' => [],
                'done' => [],
            ],
        ];

        foreach ($tasks as $task) {

            $task->checkDeadlineStatus();
            
            $statusKey = $task->status_key ?? 'to-do'; 
            if (array_key_exists($statusKey, $response['tasks'])) {
                $response['tasks'][$statusKey][] = [
                    'id' => strval($task->task_id), 
                    'content' => $task->task_name, 
                    'description' => $task->task_description,
                    'project_id' => $task->project_id,
                    'user_count' => $task->users->count(), 
                    'users' => $task->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name, 
                        ];
                    }),
                    'deadline' => $task->formatted_deadline,
                    'status' => $task->status_key,
                    'created_at' => $task->created_at,
                    'time_start' => $task->time_start,
                    'is_late' => $task->is_late,
                    'is_near_deadline' => $task->is_near_deadline,
                ];
            }
        }

        return response()->json($response);
    }

    public function createTaskToProject(Request $request, $id,NotificationService $notificationService)
    {
        $user = $request->user();
        
        $validatedData = $request->validate([
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:to-do,in-progress,verify,done', 
            'deadline' => 'required|date', 
            'time_start' => 'required|date',
            'users' => 'nullable|array', 
            'users.*' => 'exists:users,id',
        ]);
        $task = Task::create([
            'task_name' => $validatedData['task_name'],
            'project_id' => $id, 
            'task_description' => $validatedData['description'],
            'status_key' => $validatedData['status'],
            'deadline' => $validatedData['deadline'],
            'time_start' => $validatedData['time_start']
        ]);

    
        // Gán user vào task nếu có danh sách users
        if ($request->has('users')) {
            $task->users()->attach($validatedData['users']);

            // Gửi thông báo cho từng user được thêm vào task
            foreach ($validatedData['users'] as $userId) {
                $notificationService->createNotification([
                    'user_id' => $userId,
                    'notification_type' => 'info', // Loại thông báo
                    'message' => "You have been assigned the task '{$task->task_name}' by {$user->name}.",
                    'link' => "/dashboard/task"
                ]);
            }
        }

        return response()->json($task, 201);
    }
    

    // Tạo dự án mới
    public function store(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $project = Project::create($request->all());

        return response()->json($project, 201);
    }

    // Cập nhật dự án
    public function update(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $request->validate([
            'task_name' => 'sometimes|string|max:255',
            'deadline' => 'sometimes|date',
            'description' => 'nullable|string',
            'status' => 'sometimes|string',
            'time_start' => 'sometimes|date',
        ]);

        $task->status_key = $request->input('status');
        $task->save();
        $task->checkDeadlineStatus();
        return response()->json();
    }

    public function updateTaskProject(Request $request, $projectId, $taskId, NotificationService $notificationService)
    {
        $user = $request->user(); // Người thực hiện cập nhật task

        if (!$projectId || !$taskId) {
            return response()->json(['error' => 'Project ID and Task ID are required'], 400);
        }

        // Validate dữ liệu đầu vào
        $validatedData = $request->validate([
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:to-do,in-progress,verify,done',
            'deadline' => 'nullable|date',
            'users' => 'nullable|array',
            'time_start' => 'nullable|date'
        ]);

        $task = Task::where('task_id', $taskId)->where('project_id', $projectId)->firstOrFail();

        // Lưu dữ liệu cũ để kiểm tra thay đổi
        $oldData = $task->only(['task_name', 'description', 'deadline']);
        $oldUsers = $task->users()->pluck('users.id')->toArray();

        // Cập nhật thông tin task
        $task->update([
            'task_name' => $validatedData['task_name'],
            'description' => $validatedData['description'],
            'status' => $validatedData['status'],
            'deadline' => $validatedData['deadline'],
            'time_start' => $validatedData['time_start']
        ]);

        // Nếu có user mới được chỉ định, cập nhật danh sách người dùng trong task
        if (isset($validatedData['users'])) {
            $task->users()->sync($validatedData['users']);
        }
        $task->checkDeadlineStatus();

        // Danh sách user sau khi cập nhật
        $newUsers = $task->users()->pluck('users.id')->toArray();

        // 🔍 Kiểm tra xem có thay đổi về nội dung task hay không
        $notifications = [];

        if ($oldData['task_name'] !== $validatedData['task_name'] || $oldData['description'] !== $validatedData['description']) {
            $notifications[] = "Task information has been updated.";
        }

        if ($oldData['deadline'] !== $validatedData['deadline']) {
            $notifications[] = "Task deadline has been updated to {$validatedData['deadline']}.";
        }

        // Gửi thông báo cập nhật nếu có thay đổi nội dung task
        if (!empty($notifications)) {
            $message = "The task '{$task->task_name}' has been updated by {$user->name}: " . implode(' ', $notifications);

            foreach ($newUsers as $userId) {
                $notificationService->createNotification([
                    'user_id' => $userId,
                    'notification_type' => 'info',
                    'message' => $message,
                    'link' => "/dashboard/task"
                ]);
            }
        }

        // 🔍 Kiểm tra xem có user nào được thêm hoặc bị xóa không
        $addedUsers = array_diff($newUsers, $oldUsers);
        $removedUsers = array_diff($oldUsers, $newUsers);

        if (!empty($addedUsers)) {
            foreach ($addedUsers as $userId) {
                $notificationService->createNotification([
                    'user_id' => $userId,
                    'notification_type' => 'info',
                    'message' => "You have been assigned to the task '{$task->task_name}'.",
                    'link' => "/dashboard/task"
                ]);
            }
        }

        if (!empty($removedUsers)) {
            foreach ($removedUsers as $userId) {
                $notificationService->createNotification([
                    'user_id' => $userId,
                    'notification_type' => 'warning',
                    'message' => "You have been removed from the task '{$task->task_name}'.",
                    'link' => "/dashboard/task"
                ]);
            }
        }

        return response()->json();
    }



    // Xóa task
    public function destroy($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task không tồn tại'], 404);
        }

        $task->delete();

        return response()->json();
    }

    // trả về danh sách các task bị xóa mềm
    public function getDeletedTasks($projectId)
    {
        $deletedTasks = Task::onlyTrashed()->where('project_id', $projectId)->get();

        return response()->json($deletedTasks);
    }

    // khôi phục lại task bị xóa mềm
    public function restoreTask($id)
    {
        $task = Task::onlyTrashed()->find($id);

        if (!$task) {
            return response()->json(['error' => 'Task không tồn tại'], 404);
        }

        $task->restore();

        return response()->json();
    }

    // xóa vĩnh viễn task
    public function forceDeleteTask($id)
    {
        $task = Task::onlyTrashed()->find($id);

        if (!$task) {
            return response()->json(['error' => 'Task không tồn tại'], 404);
        }

        $task->forceDelete(); // Xóa vĩnh viễn task.

        return response()->json();
    }

    public function getUsersByProject($projectId)
    {
        $project = Project::findOrFail($projectId);

        // Fetch unique users from the list of user IDs
        $users = $project->users()->get();

        $users->each(function($user) {
            $user->active_tasks_count = $user->countActiveTasks();
        });

        return response()->json($users);
    }

}
