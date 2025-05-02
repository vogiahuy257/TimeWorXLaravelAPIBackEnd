<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\ProjectControllerView;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\API\PersonalPlanController;
use App\Http\Controllers\API\TaskCommentController;
use App\Http\Controllers\API\ReportCommentController;
use App\Http\Controllers\API\CalendarController;
use App\Http\Controllers\API\MeetingController;
use App\Http\Controllers\API\SummaryReportController;
use App\Http\Controllers\API\FileController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\NotificationController;


Route::middleware(['auth:sanctum'])
->get('/user', function (Request $request) {
    return $request->user();
});

// Route::middleware('auth:sanctum')->post('/v1/user/update', [UserController::class, 'updateUser']);

Route::middleware(['auth:sanctum'])
->apiResource('projects', ProjectController::class)
->except(['index','edit','create']);
    
    Route::middleware(['auth:sanctum'])->get('/v1/projects/getall', [ProjectController::class, 'index']);

    Route::middleware(['auth:sanctum'])->get('/v1/projects/history', [ProjectController::class, 'getDeletedProjects']);
    Route::delete('/v1/projects/permanently-delete/{id}', [ProjectController::class, 'permanentlyDeleteProject']);
    Route::middleware(['auth:sanctum'])->put('/v1/projects/restore/{id}', [ProjectController::class, 'restoreProject']);
    Route::post('/projects/{projectId}/users', [ProjectController::class, 'addUserToProject']);
    Route::put('/projects/{projectId}/user-role', [ProjectController::class, 'updateUserRoleInProject']);
    Route::middleware(['auth:sanctum'])->get('/v1/projects/statistics', [ProjectController::class, 'getStatisticsOfTasks']);
    Route::middleware(['auth:sanctum'])->post('/projects/{project_id}/statistics', [ProjectController::class, 'getProjectStatistics']);
        // Lấy tất cả file liên quan đến task của một project
        Route::middleware(['auth:sanctum'])->get('/v1/projects/{project_id}/files', [ProjectController::class, 'getAllFilerTaskToProject']);

    Route::delete('/v1/projects/{projectId}/remove-user', [ProjectController::class, 'removeUserFromProject']);



    // Route Projectview (TASK)
    Route::middleware(['auth:sanctum'])->apiResource('project-view', ProjectControllerView::class);
    Route::post('/project-view/{project_id}/tasks', [ProjectControllerView::class, 'createTaskToProject']);
    Route::middleware(['auth:sanctum'])->put('/project-view/{projectId}/tasks/{taskId}', [ProjectControllerView::class, 'updateTaskProject']);

    Route::middleware(['auth:sanctum'])->get('/v1/project-view/{projectId}/deleted-tasks', [ProjectControllerView::class, 'getDeletedTasks']);

    Route::middleware(['auth:sanctum'])->put('/v1/project-view/tasks/{taskId}/restore', [ProjectControllerView::class, 'restoreTask']); 
    Route::middleware(['auth:sanctum'])->delete('/v1/project-view/tasks/{taskId}/force-delete', [ProjectControllerView::class, 'forceDeleteTask']); 
    Route::get('/project-view/{project_id}/users', [ProjectControllerView::class, 'getUsersByProject']);
    Route::middleware(['auth:sanctum'])->put('/v1/project-view/{projectId}/HorizontalTaskChart',[ProjectControllerView::class, 'HorizontalTaskChart']);
    // Task
    Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
        // Route cho phương thức index (GET /api/v1/tasks)
        Route::get('tasks', [TaskController::class, 'index']);
        
        // Route cho phương thức store (POST /api/v1/tasks)
        Route::post('tasks', [TaskController::class, 'store']);
        
        // Route cho phương thức show (GET /api/v1/tasks/{task})
        // Route::get('tasks/{task}', [TaskController::class, 'show']);
        
        // Route cho phương thức update (PUT /api/v1/tasks/{task})
        Route::put('tasks/{id}', [TaskController::class, 'update']);
        
        // Route cho phương thức destroy (DELETE /api/v1/tasks/{task})
        Route::delete('tasks/{id}', [TaskController::class, 'destroy']);
    });
    
    Route::get('/tasks/{projectId}/done', [TaskController::class, 'getDoneTasksByProject']);
    //Task Comment
    Route::apiResource('task-comments', TaskCommentController::class)->except(['index']);
    Route::get('tasks/{taskId}/comments', [TaskCommentController::class, 'index']);
  
    //Route Reports
    Route::apiResource('reports', ReportController::class)->except(['index','update']);
    Route::get('reports/{projectId}/reports', [ReportController::class, 'index']);
    Route::post('reports/{report_id}',[ReportController::class, 'update']);

    // PersonalPlanController
    Route::apiResource('personal-plans', PersonalPlanController::class);
    Route::put('/personal-plans/{id}/status', [PersonalPlanController::class, 'updateStatus']);
    Route::middleware(['auth:sanctum'])->get('/v1/personal-plans/trashed', [PersonalPlanController::class, 'trashed']);
    Route::middleware(['auth:sanctum'])->post('/v1/personal-plans/{id}/restore', [PersonalPlanController::class, 'restore']);
    Route::middleware(['auth:sanctum'])->delete('/v1/personal-plans/{id}/force-delete', [PersonalPlanController::class, 'forceDelete']);

    // Route User
    Route::apiResource('users', UserController::class);
    Route::get('/users/search', [UserController::class, 'search']);
    Route::middleware(['auth:sanctum'])->get('v1/users/getall/tasks', [UserController::class, 'getAllTaskNameToUser']);

     // Xem danh sách bình luận của một báo cáo
    Route::get('/reports/{taskId}/comments/{userId}', [ReportCommentController::class, 'index']);
    Route::post('/reports/{taskId}/comments/{userId}', [ReportCommentController::class, 'store']);
    Route::delete('/reports/delete/{commentId}/{userId}', [ReportCommentController::class, 'destroy']);
    Route::post('/reports/comments/{commentId}/pin', [ReportCommentController::class, 'pinComment']);
    Route::post('/reports/comments/{commentId}/unpin', [ReportCommentController::class, 'unpinComment']);

    Route::post('/calendar/update-event/{eventId}', [CalendarController::class, 'updateEvent']);

    Route::middleware(['auth:sanctum'])->get('/meetings', [MeetingController::class, 'getUserMeetings']);
    Route::post('/meetings', [MeetingController::class, 'createMeeting']);
    Route::middleware(['auth:sanctum'])->put('/meetings/{meetingId}', [MeetingController::class, 'updateMeeting']);
    Route::delete('/meetings/{meetingId}', [MeetingController::class, 'deleteMeeting']);

    Route::middleware(['auth:sanctum'])->group(function () {
        // Tạo báo cáo tổng hợp mới
        Route::post('/summary-reports', [SummaryReportController::class, 'createSummaryReport']);
        // Lấy danh sách các báo cáo tổng hợp với tìm kiếm & bộ lọc
        Route::get('/summary-reports', [SummaryReportController::class, 'getSummaryReports']);
        // Lấy thông tin chi tiết của một báo cáo tổng hợp theo ID
        Route::get('/summary-reports/{id}', [SummaryReportController::class, 'getSummaryReportById']);
        // Tải file ZIP của báo cáo tổng hợp
        Route::get('/summary-reports/{id}/download', [SummaryReportController::class, 'downloadSummaryReportZip']);
        
        // Xóa mềm báo cáo tổng hợp (chuyển vào thùng rác, có thể khôi phục)
        Route::delete('/summary-reports/{id}', [SummaryReportController::class, 'softDeleteSummaryReport']);
        // Xóa vĩnh viễn báo cáo tổng hợp (xóa hoàn toàn, không thể khôi phục)
        Route::delete('/summary-reports/{id}/permanent', [SummaryReportController::class, 'permanentlyDeleteSummaryReport']);
        // Lấy danh sách các báo cáo đã bị xóa mềm (để người dùng có thể xem và khôi phục)
        Route::post('/summary-reports/deleted', [SummaryReportController::class, 'getDeletedSummaryReports']);
    
        // Khôi phục một báo cáo đã bị xóa mềm
        Route::post('/summary-reports/{id}/restore', [SummaryReportController::class, 'restoreSummaryReport']);
    });
    

    Route::middleware(['auth:sanctum'])->get('/files/download', [FileController::class, 'downloadFile']);

    // setting system controller
    Route::middleware(['auth:sanctum'])->group(function () {
        // Lấy và cập nhật cài đặt chung của user
        Route::get('/v1/settings', [SettingController::class, 'show']); // Lấy setting
        Route::put('/v1/settings', [SettingController::class, 'update']); // Cập nhật setting
    
        // Cập nhật tên user
        Route::put('/v1/settings/update-name', [SettingController::class, 'updateUserName']);
    
        // Cập nhật email user
        Route::put('/v1/settings/update-email', [SettingController::class, 'updateUserEmail']);
    
        // Cập nhật ảnh đại diện user
        // chưa làm
        Route::post('/v1/settings/update-profile-picture', [SettingController::class, 'updateUserProfilePicture']);
    
        // Cập nhật mật khẩu user
        Route::put('/v1/settings/update-password', [SettingController::class, 'updateUserPassword']);
    
        // Xóa tài khoản user
        // chưa làm
        Route::post('/v1/settings/delete-account', [SettingController::class, 'deleteAccount']);
    });

Route::middleware('auth:sanctum')
->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/markAsRead', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/markAllAsRead', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);
    Route::post('/notifications', [NotificationController::class, 'store']);
});