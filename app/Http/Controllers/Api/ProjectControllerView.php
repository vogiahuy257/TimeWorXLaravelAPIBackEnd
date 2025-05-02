<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Services\ProjectStatusBroadcastService;
use App\Services\TaskStatusBroadcastService;
use Illuminate\Support\Facades\DB;


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
        $project = Project::with('manager')->findOrFail($id);

        $tasks = Task::where('project_id', $id)->with('users')->get();

        $taskCount = $tasks->count();
        $inProgressCount = $tasks->where('status_key', 'in-progress')->count();
        $doneCount = $tasks->where('status_key', 'done')->count();
        $todoCount = $tasks->where('status_key', 'to-do')->count();
        $verifyCount = $tasks->where('status_key', 'verify')->count();
        $lateCount = 0;
        $nearDeadlineCount = 0;
        $response = [
            'project' => [
                'id' => $project->project_id, 
                'name' => $project->project_name, 
                'description' => $project->project_description, 
                'deadline' => $project->end_date,
                'user_count' => $project->countProjectUsers(),
                'start_date' => $project->start_date,
                'project_priority' => $project->project_priority,
                'project_manager' => $project->project_manager,
                'taskCount' => $taskCount,
                'inProgress' => $inProgressCount,
                'done' => $doneCount,
                'todo' => $todoCount,
                'verify' => $verifyCount,
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

            if ($task->is_late) {
                $lateCount++;
            }
            if ($task->is_near_deadline) {
                $nearDeadlineCount++;
            }

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
                    'priority' => $task->priority,
                    'in_charge_user_name' => $task->inChargeUser?->name,
                    'in_charge_user_id' => $task->in_charge_user_id,
                    'deadline' => $task->formatted_deadline,
                    'status' => $task->status_key,
                    'created_at' => $task->created_at,
                    'time_start' => $task->time_start,
                    'is_late' => $task->is_late,
                    'is_near_deadline' => $task->is_near_deadline,
                ];
            }
        }
        // Gán lại sau khi đếm xong
        $response['project']['taskLateDeadline'] = $lateCount;
        $response['project']['taskNearDeadline'] = $nearDeadlineCount;

        return response()->json($response);
    }

    public function createTaskToProject(Request $request, $id)
    {
        $user = $request->user();
        
        $validatedData = $request->validate([
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:to-do,in-progress,verify,done', 
            'deadline' => 'required|date', 
            'time_start' => 'required|date',
            'assigned_to_user_id' => 'nullable|string',
            'in_charge_user_id' => 'nullable|string',
            'priority' => 'nullable|string', // Thêm quy tắc cho priority
            'users' => 'nullable|array', 
            'users.*' => 'exists:users,id',
        ]);
        $task = Task::create([
            'task_name' => $validatedData['task_name'],
            'project_id' => $id, 
            'task_description' => $validatedData['description'],
            'status_key' => $validatedData['status'],
            'deadline' => $validatedData['deadline'],
            'time_start' => $validatedData['time_start'],
            'priority' => $validatedData['priority'] ?? 'medium', // nếu không có thì mặc định là 'medium'
            'assigned_to_user_id' => $validatedData['assigned_to_user_id'] ?? $request->user()->id,
            'in_charge_user_id' => $validatedData['in_charge_user_id'] ?? $request->user()->id,
        ]);

        $inChargeUserId = $validatedData['in_charge_user_id'] ?? null;

        if ($inChargeUserId) {
            $this->notificationService->sendNotification(
                $inChargeUserId,
                'info',
                "You have been assigned as the in-charge user for the task '{$validatedData['task_name']}' by {$user->name}.",
                '/dashboard/project/' . $task->project_id . '/broad'
            );
        }

    
        // Gán user vào task nếu có danh sách users
        if ($request->has('users')) {
            $task->users()->attach($validatedData['users']);

            // Gửi thông báo cho từng user được thêm vào task
            foreach ($validatedData['users'] as $userId) {
                $this->notificationService->createNotification([
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
        $user = $request->user(); // Người thực hiện cập nhật task
        /** @var \App\Models\Task $task */
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $request->validate([
            'status' => 'required|string|in:to-do,in-progress,verify,done',
        ]);

        $task->status_key = $request->input('status');
        $task->save();
        $project = Project::where('project_id', $task->project_id)->first();
        // Gửi capnhat trạng thái dự án
        (new ProjectStatusBroadcastService($project))->updateAndSendProjectStatus($request->input('status'));

        (new TaskStatusBroadcastService)->sendStatusUpdate($task, $request->input('status'));

        // Gửi thông báo cho người được giao nhiệm vụ
        $this->notificationService->sendNotificationTaskStatusToAll($task, $project, $request->input('status'),$user);
    
        $task->checkDeadlineStatus();
        return response()->json();
    }

    public function updateTaskProject(Request $request, $projectId, $taskId)
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
            'assigned_to_user_id' => 'nullable|string',
            'in_charge_user_id' => 'nullable|string',
            'priority' => 'nullable|string',
            'deadline' => 'nullable|date',
            'users' => 'nullable|array',
            'time_start' => 'nullable|date'
        ]);

        $task = Task::where('task_id', $taskId)->where('project_id', $projectId)->firstOrFail();

        // Lưu dữ liệu cũ để kiểm tra thay đổi
        $oldData = $task->only(['task_name', 'status_key','description', 'deadline','priority','in_charge_user_id']);
        $oldUsers = $task->users()->pluck('users.id')->toArray();

        if (isset($validatedData['users'])) {
            $task->syncUsers($validatedData['users']);
        }
        
        // Cập nhật thông tin task
        $task->update([
            'task_name' => $validatedData['task_name'],
            'task_description' => $validatedData['description'],
            'assigned_to_user_id' => $validatedData['assigned_to_user_id'] ?? $task->assigned_to_user_id,
            'in_charge_user_id' => $validatedData['in_charge_user_id'] ?? $task->in_charge_user_id,
            'priority' => $validatedData['priority'] ?? $task->priority,
            'status' => $validatedData['status'],
            'deadline' => $validatedData['deadline'],
            'time_start' => $validatedData['time_start']
        ]);
        $task->checkDeadlineStatus();

        $newStatus = $validatedData['status'];
        // Danh sách user sau khi cập nhật
        $newUsers = $task->users()->pluck('users.id')->toArray();

        // 🔍 Kiểm tra xem có thay đổi về nội dung task hay không
        $notifications = [];

        if ($oldData['task_name'] !== $validatedData['task_name'] || $oldData['description'] !== $validatedData['description'] || $oldData['priority'] !== $validatedData['priority']) {
            $notifications[] = "Task information has been updated.";
        }

        if ($oldData['deadline'] !== $validatedData['deadline']) {
            $notifications[] = "Task deadline has been updated to {$validatedData['deadline']}.";
        }

        // 🔍 Kiểm tra xem có user nào được thêm hoặc bị xóa không
        $addedUsers = array_diff($newUsers, $oldUsers);

        if (!empty($addedUsers)) {
            foreach ($addedUsers as $userId) {
                $this->notificationService->sendNotification(
                    $userId,
                    'info',
                    "You have been assigned to the task '{$task->task_name}'.",
                    "/dashboard/task"
                );
            }
        }

        if (($validatedData['in_charge_user_id'] ?? null) && ($validatedData['in_charge_user_id'] ?? null) != $task->in_charge_user_id) {
            $this->notificationService->sendNotification(
                $validatedData['in_charge_user_id'],
                'info',
                "You have been assigned as the in-charge user for the task '{$task->task_name}' by {$user->name}.",
                "/dashboard/project/{$projectId}/broad"
            );
        }

        if ($oldData['status_key'] == 'verify' && $newStatus != 'done' && $newStatus != 'verify') {
            // Gửi thông báo cho người dùng được chỉ định
            foreach ($newUsers as $userId) {
                $this->notificationService->sendNotification(
                    $userId,
                    'error',
                    "Task '{$task->task_name}' has been unverified.",
                    "/dashboard/task"
                );
            }
        }

        
        // Gửi thông báo cập nhật nếu có thay đổi nội dung task
        if (!empty($notifications)) {
            $message = "The task '{$task->task_name}' has been updated by {$user->name}: " . implode(' ', $notifications);

            foreach ($newUsers as $userId) {
                $this->notificationService->sendNotification(
                    $userId,
                    'info',
                    $message,
                    "/dashboard/task"
                );
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

    public function HorizontalTaskChart(Request $request, $projectId)
    {
        $taskStats = DB::table('task_user')
            ->join('users', 'task_user.user_id', '=', 'users.id')
            ->join('tasks', 'task_user.task_id', '=', 'tasks.task_id')
            ->select(
                'users.name as username',
                DB::raw("SUM(CASE WHEN tasks.status_key = 'to-do' THEN 1 ELSE 0 END) as todo"),
                DB::raw("SUM(CASE WHEN tasks.status_key = 'in_progress' THEN 1 ELSE 0 END) as in_progress"),
                DB::raw("SUM(CASE WHEN tasks.status_key = 'verify' THEN 1 ELSE 0 END) as verify"),
                DB::raw("SUM(CASE WHEN tasks.status_key = 'done' THEN 1 ELSE 0 END) as done"),
                DB::raw("COUNT(tasks.task_id) as countTask")
            )
            ->where('tasks.project_id', $projectId)
            ->groupBy('users.name')
            ->get();
    
        return response()->json($taskStats);
    }
    

}
