<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Models\PersonalPlan;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Services\ProjectStatusBroadcastService;
use App\Models\Project;
use App\Services\TaskStatusBroadcastService;

class TaskController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of the tasks.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $user = User::find($userId);
        $tasks = $user->tasks()->with('project')->get();
        $personalPlans = PersonalPlan::where('user_id', $userId)->get();

        $projectId = request()->input('project_id');  

        $response = [
            'projects' => [],
            'tasks' => [
                'to-do' => [],
                'in-progress' => [],
                'verify' => [],
                'done' => [],
            ],
            'personalPlans' => [
                'to-do' => [],
                'in-progress' => [],
                'verify' => [],
                'done' => [],
            ],
        ];

        // Trường hợp lấy toàn bộ các $tasks và $personalPlans
        if (is_null($projectId) || $projectId === "") {
            // Lấy toàn bộ tasks
            foreach ($tasks as $task) {
                $this->addTaskToResponse($task, $response);
            }
            foreach ($personalPlans as $plan) {
                $this->addPersonalPlanToResponse($plan, $response);
            }
        }
        // Trường hợp lấy các personalPlans và không lấy tasks nhưng vẫn phải có danh sách projects
        elseif ($projectId === "personalPlan") {
            foreach ($personalPlans as $plan) {
                $this->addPersonalPlanToResponse($plan, $response);
            }
            foreach ($tasks as $task) {
                if (!in_array($task->project_id, array_column($response['projects'], 'id'))) {
                    $response['projects'][] = [
                        'id' => $task->project_id,
                        'name' => $task->getProjectName() ?? 'Unknown',
                    ];
                }
            }
        }
        // Còn lại chỉ lấy các task có project_id bằng với request()->input('project_id')
        else {
            foreach ($tasks as $task) {
                if ($task->project_id == $projectId) {
                    $this->addTaskToResponse($task, $response);
                }
                //lấy dữ liệu
                if (!in_array($task->project_id, array_column($response['projects'], 'id'))) {
                    $response['projects'][] = [
                        'id' => $task->project_id,
                        'name' => $task->getProjectName() ?? 'Unknown',
                    ];
                }
            }
        }
        return response()->json($response);
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request)
    {
        $userId = $request->user()->id;
        // Validate các dữ liệu được gửi từ form
        $validatedData = $request->validate([
            'project_id' => 'required',
            'task_name' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'in_charge_user_id' => 'nullable|exists:users,id',
            'status_key' => 'required',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'deadline' => 'nullable|date',
            'time_start' => 'nullable|date'
        ]);

        if($validatedData['in_charge_user_id'] == null) {
            $validatedData['in_charge_user_id'] = $userId;
        }

        // Tạo task mới
        $task = Task::create($validatedData);
        return response()->json($task, 201);
    }


    /**
     * Display the specified task.
     */
    public function show($id)
    {
        // Tìm task theo ID
        $task = Task::with('project')->find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        // Khởi tạo dữ liệu phản hồi
        $response = [
            'task' => [
                'id' => strval($task->task_id),
                'content' => $task->task_name,
                'project_id' => $task->project_id,
                'project_name' => $task->getProjectName(),
                'description' => $task->task_description,
                'user_count' => $task->users->count(),
                'users' => $task->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                    ];
                }),
                'priority' => $task->priority,
                'in_charge_user_name' => $task->inChargeUser?->name,
                'deadline' => $task->formatted_deadline,
                'status' => $task->status_key,
                'is_late' => $task->is_late,
                'is_near_deadline' => $task->is_near_deadline,
                'time_start' => $task->time_start,
            ],
            'project' => [
                'id' => $task->project_id,
                'name' => $task->getProjectName(),
            ]
        ];

        return response()->json($response);
    }


    
    private function addTaskToResponse($task, &$response)
    {
        $task->checkDeadlineStatus();

        $statusKey = $task->status_key ?? 'to-do';
        if (array_key_exists($statusKey, $response['tasks'])) {
            $response['tasks'][$statusKey][] = [
                'id' => strval($task->task_id),
                'content' => $task->task_name,
                'project_id' => $task->project_id,
                'project_name' => $task->getProjectName(),
                'description' => $task->task_description,
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
                'is_late' => $task->is_late,
                'is_near_deadline' => $task->is_near_deadline,
                'time_start' => $task->time_start,
            ];

            if (!in_array($task->project_id, array_column($response['projects'], 'id'))) {
                $response['projects'][] = [
                    'id' => $task->project_id,
                    'name' => $task->getProjectName() ?? 'Unknown',
                ];
            }
        }
    }

    private function addPersonalPlanToResponse($plan, &$response)
    {
        $statusKey = $plan->plan_status ?? 'to-do';

        if (array_key_exists($statusKey, $response['personalPlans'])) {
            $response['personalPlans'][$statusKey][] = [
                'id' => strval($plan->plan_id),
                'name' => $plan->plan_name,
                'description' => $plan->plan_description,
                'start_date' => $plan->formatted_start_date,
                'end_date' => $plan->formatted_end_date,
                'status' => $plan->plan_status,
                'priority' => $plan->plan_priority,
            ];
        }
    }


    /**
     * Update the specified task in storage.
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\Task $task */
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $validatedData = $request->validate([
            'status_key' => 'sometimes|string',
        ]);

        $task->update($validatedData);

        $project = Project::where('project_id', $task->project_id)->first();
        if(isset($validatedData['status_key']))
        {
            // Gửi capnhat trạng thái dự án
            (new ProjectStatusBroadcastService($project))->updateAndSendProjectStatus($validatedData['status_key']);

            // Gửi capnhat trạng thái task
            (new TaskStatusBroadcastService)->sendStatusUpdate($task, $validatedData['status_key']);

            // Gửi thông báo cho người được giao nhiệm vụ
            if (in_array($validatedData['status_key'], ['done', 'verify'])) {
                $this->notificationService->sendNotificationTaskStatusToManager($task, $project, $validatedData['status_key']);
            }            
            
        }
        $task->checkDeadlineStatus();
        return response()->json($task);
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task)
    {
        // Xóa mềm task
        $task->delete();
        return response()->json(['message' => 'Task deleted successfully']);
    }

     /**
     * Display a listing of completed (done) tasks for a given project,
     * including tasks that have been soft deleted.
     */
    public function getDoneTasksByProject($projectId)
    {
        $tasks = Task::withTrashed()
            ->where('project_id', $projectId)
            ->whereIn('status_key', ['done', 'verify'])
            ->select('project_id', 'task_id', 'task_name','status_key' ,'task_description','updated_at')
            ->get();

        return response()->json($tasks);
    }
}
