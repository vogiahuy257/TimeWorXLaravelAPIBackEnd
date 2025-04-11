<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Report;
use App\Services\NotificationService;

class ProjectController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Lấy danh sách các dự án mà user đó là project manager hoặc là thành viên của dự án đó (với quyền project manager) 
        try {
            if (!$request->user()) {
                \Log::error('Không thể lấy user từ request');
                return response()->json(['message' => 'Không thể xác định người dùng'], 401);
            }

            $user_id = $request->user()->id;

            $projects = Project::nonDeleted()
            ->where(function ($query) use ($user_id) 
            {
                $query->where('project_manager', $user_id)
                    ->orWhereHas('users', function ($query) use ($user_id) 
                    {
                        $query->where('user_id', $user_id)
                                ->where('is_project_manager', true);
                    });
            })
            ->get();

            // Cập nhật trạng thái và thông tin cho từng dự án
            foreach ($projects as $project) {
                $project->updateProjectStatus();
                $project->late_tasks_count = $project->countLateTasks();
                $project->near_deadline_tasks_count = $project->countNearDeadlineTasks();
                $project->completed_tasks_ratio = $project->countTasksAndCompleted();
            }

            return response()->json($projects);

        } catch (\Exception $e) {
            \Log::error('Lỗi khi lấy danh sách projects', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Lỗi hệ thống'], 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:100',
            'project_description' => 'nullable|string',
            'project_priority' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'project_status'=>'nullable|string|max:200',
        ]);

        // Gán project_manager là user_id
        $validated['project_manager'] = $request->user()->id;

        // Tạo dự án mới
        $project = Project::create($validated);

        $project->updateProjectStatus();
        $project->late_tasks_count = $project->countLateTasks();
        $project->near_deadline_tasks_count = $project->countNearDeadlineTasks();
        $project->completed_tasks_ratio = $project->countTasksAndCompleted();

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $user_id)
    {

        // sửa lại phương thức này là phương thức xem dự án vì index đã đổi thành dư án
        $projects = Project::nonDeleted()
        ->where(function ($query) use ($user_id) 
        {
            $query->where('project_manager', $user_id)
                  ->orWhereHas('users', function ($query) use ($user_id) 
                  {
                      $query->where('user_id', $user_id)
                            ->where('is_project_manager', true);
                  });
        })
        ->get();

        // Cập nhật trạng thái và thông tin cho từng dự án
        foreach ($projects as $project) {
            $project->updateProjectStatus();
            $project->late_tasks_count = $project->countLateTasks();
            $project->near_deadline_tasks_count = $project->countNearDeadlineTasks();
            $project->completed_tasks_ratio = $project->countTasksAndCompleted();
        }

        return response()->json($projects);
    }

    //show all user delete

    public function getDeletedProjects(Request $request)
    {
        // lấy id user từ xác thực sanscum
        $user_id = $request->user()->id;
        // Lấy các dự án đã bị xóa cho người quản lý dự án
        $deletedProjects = Project::deletedProjectsByUser($user_id)->get();

        // Trả về danh sách dự án đã bị xóa hoặc thông báo không tìm thấy dự án
        return response()->json($deletedProjects);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user_id = $request->user()->id;
        $project = Project::find($id); 
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        if (!$project->isUserProjectManager($user_id)) {
            return response()->json(['error' => 'Update false'], 403);
        }

        $validated = $request->validate([
            'project_name' => 'required|string|max:100',
            'project_description' => 'nullable|string',
            'start_date' => 'required|date',
            'project_priority' => 'nullable|string',
            'end_date' => 'nullable|date',
            'project_status'=>'nullable|string|max:200',
        ]);

        $project->update($validated);
        $project->updateProjectStatus();
        

        return response()->json(['project' => $project->fresh()]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $project = Project::findOrFail($id);

        $project->delete();
        return response()->json(null, 204);
    }

    public function permanentlyDeleteProject(string $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        $project->forceDelete();
        
        return response()->json();
    }

    public function restoreProject(string $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        $project->restore();
    
        return response()->json();
    }

    //thêm người dùng vào dự án
    public function addUserToProject(Request $request,$projectId,NotificationService $notificationService)
    {
        $userId = $request->input('user_id');

        // Kiểm tra xem người dùng đã có trong dự án chưa
        $project = Project::find($projectId);

        // kiểm tra có tồn tại trong dự án hay không
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        // kiểm tra người dùng đó có trong dự án hay không
        if ($project->users()->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'User already added to this project'], 400);
        }

        $project->users()->attach($userId);

        // Gửi thông báo cho người dùng
         $notificationService->createNotification([
            'user_id' => $userId,
            'notification_type' => 'success', // Loại thông báo thành công
            'message' => "You have been added to the project: {$project->name}",
            'link' => "/dashboard/project/{$projectId}/broad"
        ]);

        return response()->json();
    }

    //xóa người dùng khỏi dự án
    public function removeUserFromProject(Request $request, $projectId,$userId,NotificationService $notificationService)
    {
        $user_id = $request->user()->id;

        // Kiểm tra xem dự án có tồn tại không
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        // Kiểm tra xem người dùng có trong dự án không
        if (!$project->users()->where('user_id', $user_id)->exists()) {
            return response()->json(['message' => 'User not found in this project'], 400);
        }
        
        //kiểm tra người bị xóa có phải là manager không
        if($project->isUserProjectManager($user_id))
        {
            return response()->json(['message' => 'Cannot remove the project manager'], 400);
        }

        //kiểm tra người dùng thực hiện xóa có phải là manager không (chỉ manager mới có quyền xóa dự án)
        if($project->isUserProjectManager($userId))
        {
            // Xóa người dùng khỏi dự án
            $project->users()->detach($user_id);
            // Gửi thông báo cho người dùng
            $notificationService->createNotification([
                'user_id' => $user_id,
                'notification_type' => 'error', // Thông báo thông tin
                'message' => "You have been removed from the project: {$project->name}",
                'link' => null
            ]);

            return response()->json();
        }

        return response()->json();
    }


    public function updateUserRoleInProject(Request $request, string $projectId,NotificationService $notificationService)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid',
            'is_project_manager' => 'required|boolean',
        ]);

        $project = Project::findOrFail($projectId);
        $userId = $validated['user_id'];
        $isProjectManager = $validated['is_project_manager'];
        // Cập nhật quyền cho người dùng
        $project->users()->updateExistingPivot($validated['user_id'], [
            'is_project_manager' => $validated['is_project_manager']
        ]);

        $role = $isProjectManager ? 'Manager' : 'Staff';

        $notificationService->createNotification([
            'user_id' => $userId,
            'notification_type' => 'info', // Loại thông báo
            'message' => "Your role in the project '{$project->name}' has been updated to {$role}.",
            'link' => "/dashboard/project/{$projectId}/broad"
        ]);

        return response()->json(['message' => 'User role updated successfully']);
    }

    public function getStatisticsOfTasks(Request $request)
    {
        $user_id = $request->user()->id;
        $projects = Project::nonDeleted()
        ->where(function ($query) use ($user_id) {
            $query->where('project_manager', $user_id)
                  ->orWhereHas('users', function ($query) use ($user_id) {
                      $query->where('user_id', $user_id)
                            ->where('is_project_manager', true);
                  });
        })
        ->get();

        foreach ($projects as $project) {
            $project->updateProjectStatus();
            $project->statistics = $project->countTasksByStatus();;
        }
    
        return response()->json($projects);
    }

    public function getProjectStatistics(Request $request, $project_id)
    {
        $user_id = $request->user()->id;
    
        $project = Project::select('project_id')
            ->nonDeleted()
            ->where('project_id', $project_id)
            ->where(function ($query) use ($user_id) {
                $query->where('project_manager', $user_id)
                    ->orWhereHas('users', function ($query) use ($user_id) {
                        $query->where('user_id', $user_id)
                              ->where('is_project_manager', true);
                    });
            })
            ->first();
    
        // Kiểm tra nếu không tìm thấy project
        if (!$project) {
            return response()->json(['message' => 'Project not found or access denied.'], 404);
        }
    
        $statistics = $project->countTasksByStatus();
    
        return response()->json(['statistics' => $statistics]);
    }
    
    public function getAllFilerTaskToProject(Request $request, int $project_id)
    {
        $files = Report::getFilesByProject($project_id);
    
        return response()->json($files);
    }
}
