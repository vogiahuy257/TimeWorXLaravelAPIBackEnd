<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSummaryReportRequest extends FormRequest
{
    /**
     * Kiểm tra quyền người dùng trước khi gửi request
     */
    public function authorize(): bool
    {
        $user = $this->user(); // Lấy thông tin user từ request
        $projectId = $this->input('project_id');

        return $user && $user->projects()->where('project_id', $projectId)->exists();
    }

    /**
     * Quy tắc validation
     */
    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,project_id',
            'name' => 'required|string|max:255',
            'report_date' => 'required|date',
            'summary' => 'required|string|max:5000',
            'completed_tasks' => 'nullable|string|max:5000',
            'upcoming_tasks' => 'nullable|string|max:5000',
            'project_issues' => 'nullable|string|max:5000',
            'report_files' => 'nullable|array',
            'report_files.*' => 'nullable|string|max:255',
        ];
    }
}
