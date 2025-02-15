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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->apiResource('projects', ProjectController::class)->except(['index']);
    
    Route::middleware(['auth:sanctum'])->get('/v1/projects/getall', [ProjectController::class, 'index']);

    Route::get('/projects/deleted/{user_id}', [ProjectController::class, 'getDeletedProjects']);
    Route::delete('/projects/permanently-delete/{id}', [ProjectController::class, 'permanentlyDeleteProject']);
    Route::get('/projects/restore/{id}', [ProjectController::class, 'restoreProject']);
    Route::post('/projects/{projectId}/users', [ProjectController::class, 'addUserToProject']);
    Route::put('/projects/{projectId}/user-role', [ProjectController::class, 'updateUserRoleInProject']);
    Route::middleware(['auth:sanctum'])->get('/v1/projects/statistics', [ProjectController::class, 'getStatisticsOfTasks']);
    Route::middleware(['auth:sanctum'])->post('/projects/{project_id}/statistics', [ProjectController::class, 'getProjectStatistics']);

    Route::delete('/v1/projects/{projectId}/remove-user', [ProjectController::class, 'removeUserFromProject']);



    // Route Projectview (TASK)
    Route::middleware(['auth:sanctum'])->apiResource('project-view', ProjectControllerView::class);
    Route::post('/project-view/{project_id}/tasks', [ProjectControllerView::class, 'createTaskToProject']);
    Route::middleware(['auth:sanctum'])->put('/project-view/{projectId}/tasks/{taskId}', [ProjectControllerView::class, 'updateTaskProject']);

    Route::get('/project-view/{projectId}/deleted-tasks', [ProjectControllerView::class, 'getDeletedTasks']);

    Route::put('/project-view/tasks/{taskId}/restore', [ProjectControllerView::class, 'restoreTask']); 
    Route::delete('/project-view/tasks/{taskId}/force-delete', [ProjectControllerView::class, 'forceDeleteTask']); 
    Route::get('/project-view/{project_id}/users', [ProjectControllerView::class, 'getUsersByProject']);

    // Task
    Route::middleware(['auth:sanctum'])->apiResource('tasks', TaskController::class);
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
    Route::get('/personal-plans/trashed/{user_id}', [PersonalPlanController::class, 'trashed']);
    Route::post('/personal-plans/{id}/restore', [PersonalPlanController::class, 'restore']);
    Route::delete('/personal-plans/{id}/force-delete', [PersonalPlanController::class, 'forceDelete']);

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
        Route::get('/summary-reports', [SummaryReportController::class, 'getSummaryReports']);
        Route::get('/summary-reports/{id}', [SummaryReportController::class, 'getSummaryReportById']);
        Route::post('/summary-reports', [SummaryReportController::class, 'createSummaryReport']);
    });

    Route::middleware(['auth:sanctum'])->get('/files/download', [FileController::class, 'downloadFile']);