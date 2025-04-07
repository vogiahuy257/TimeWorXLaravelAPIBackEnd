<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SummaryReport;
use Illuminate\Http\Request;
use App\Services\ReportZipper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Project;
class SummaryReportController extends Controller
{
    protected ReportZipper $zipper;

    public function __construct(ReportZipper $zipper)
    {
        $this->zipper = $zipper;
    }

    /**
     * Tạo báo cáo tổng hợp và nén file.
     */
    public function createSummaryReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'report_date' => 'nullable|date',
                'project_id' => 'nullable|integer',
                'summary' => 'nullable|string',
                'completed_tasks' => 'nullable|string',
                'upcoming_tasks' => 'nullable|string',
                'project_issues' => 'nullable|string',
                'report_files' => 'array|nullable', // Cho phép null hoặc một mảng
                'report_files.*.file_id' => 'nullable|integer', // Có thể null hoặc số nguyên
                'report_files.*.path' => 'nullable|string', // Có thể null hoặc chuỗi
                'report_files.*.file_name' => 'nullable|string', // Có thể null hoặc chuỗi
            ]);            

            $userId = $request->user()->id;
            $validated['reported_by_user_id'] = $userId;
            $storedFiles = [];

            foreach ($validated['report_files'] as $file) {
                if (Storage::disk('public')->exists($file['path'])) {
                    $storedFiles[$file['path']] = $file['file_name']; // Lưu key-value đúng format
                }
            }

            if (!empty($validated['project_id'])) {
                $project = Project::find($validated['project_id']);
                if ($project) {
                    $validated['project_name'] = $project->project_name;
                    $validated['project_description'] = $project->project_description;
                }
            }

            return DB::transaction(function () use ($validated, $storedFiles) {
                $zipFileName = null;
                $zipFilePath = null;

                // 📌 Tạo file ZIP nếu có file hợp lệ
                if (!empty($storedFiles)) {
                    $zipFileName = 'summary_report_' . time() . '.zip';
                    $zipFilePath = $this->zipper->createZip($zipFileName, $storedFiles);
                }

                // 📌 Lưu vào database
                $summaryReport = SummaryReport::create([
                    'name' => $validated['name'],
                    'report_date' => $validated['report_date'] ?? now(),
                    'project_id' => $validated['project_id'] ?? null,
                    'project_name' => $validated['project_name'] ?? null,
                    'project_description' => $validated['project_description'] ?? null,
                    'reported_by_user_id' => $validated['reported_by_user_id'],
                    'summary' => $validated['summary'] ?? null, // ✅ Bổ sung
                    'completed_tasks' => $validated['completed_tasks'] ?? null, // ✅ Bổ sung
                    'upcoming_tasks' => $validated['upcoming_tasks'] ?? null, // ✅ Bổ sung
                    'project_issues' => $validated['project_issues'] ?? null, // ✅ Bổ sung
                    'zip_file_path' => $zipFilePath,
                    'zip_name' => $zipFileName,
                ]);                

                return response()->json($summaryReport);
            });
        } catch (\Exception $e) {
            \Log::error('Error creating summary report: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create summary report.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Lấy danh sách summary reports với tìm kiếm và bộ lọc.
     */
    public function getSummaryReports(Request $request)
    {
        $userId = $request->user()->id;
        $search = trim(strtolower($request->input('search', null)));
        $startDate = $request->input('start_date', null);
        $endDate = $request->input('end_date', null);
        $perPage = min($request->input('per_page', 10), 50); // Giới hạn tối đa 50

        $query = SummaryReport::where('reported_by_user_id', $userId);

        if ($search) {
            $query->whereRaw('LOWER(name) LIKE ?', ["%$search%"]); // Tìm kiếm không phân biệt hoa thường
        }

        if ($startDate && strtotime($startDate)) {
            $query->where('report_date', '>=', $startDate);
        }
        if ($endDate && strtotime($endDate)) {
            $query->where('report_date', '<=', $endDate);
        }

        $summaryReports = $query->orderBy('report_date', 'desc')->paginate($perPage);

        return response()->json($summaryReports);
    }


    /**
     * Lấy thông tin chi tiết của một summary report.
     */
    public function getSummaryReportById(Request $request, mixed $id)
    {
        $userId = $request->user()->id;
        
        $summaryReport = SummaryReport::where('summary_report_id', $id)
            ->where('reported_by_user_id', $userId)
            ->first();

        if (!$summaryReport) {
            return response()->json(['message' => 'Report not found or unauthorized access.'], 403);
        }

        return response()->json( $summaryReport);
    }

    /**
     * API để tải file ZIP của báo cáo tổng hợp.
     */
    public function downloadSummaryReportZip(Request $request, int $id)
    {
        $userId = $request->user()->id;

        $summaryReport = SummaryReport::where('summary_report_id', $id)
            ->where('reported_by_user_id', $userId)
            ->first();

        if (!$summaryReport || !$summaryReport->zip_name) {
            return response()->json(['message' => 'Report or ZIP file not found.'], 404);
        }

        try {
            return $this->zipper->downloadZip($summaryReport->zip_name);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }


    /**
     * Xóa mềm (đưa vào thùng rác)
     */
    public function softDeleteSummaryReport(Request $request, int $id)
    {
        $userId = $request->user()->id;

        $summaryReport = SummaryReport::where('summary_report_id', $id)
            ->where('reported_by_user_id', $userId)
            ->first();

        if (!$summaryReport) {
            return response()->json(['message' => 'Report not found.'], 403);
        }

        $summaryReport->delete();

        return response()->json(['message' => 'Summary report moved to trash successfully!']);
    }

    /**
     * Xóa vĩnh viễn (xóa hoàn toàn, kể cả file ZIP)
     */
    public function permanentlyDeleteSummaryReport(Request $request, int $id)
    {
        $userId = $request->user()->id;

        $summaryReport = SummaryReport::withTrashed()
            ->where('summary_report_id', $id)
            ->where('reported_by_user_id', $userId)
            ->first();

        if (!$summaryReport) {
            return response()->json(['message' => 'Report not found.'], 403);
        }

        return DB::transaction(function () use ($summaryReport) {
            if ($summaryReport->zip_file_path) {
                $filePath = storage_path('app/' . $summaryReport->zip_file_path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $summaryReport->forceDelete();

            return response()->json(['message' => 'Summary report permanently deleted!']);
        });
    }

    /**
     * Lấy danh sách các báo cáo đã bị xóa mềm.
     */
    public function getDeletedSummaryReports(Request $request)
    {       
            $userId = $request->user()->id;
        
            $deletedReports = SummaryReport::onlyTrashed()
            ->where('reported_by_user_id', $userId)
            ->orderBy('deleted_at', 'desc')
            ->get();
            
            if ($deletedReports->isEmpty()) {
                return response()->json(['message' => 'No deleted reports found.'], 404);
            }
        
            return response()->json($deletedReports);
    }

    /**
     * Khôi phục một báo cáo đã bị xóa mềm.
     */
    public function restoreSummaryReport(Request $request, mixed $id)
    {
        $userId = $request->user()->id;

        $summaryReport = SummaryReport::onlyTrashed()
            ->where('summary_report_id', $id)
            ->where('reported_by_user_id', $userId)
            ->first();

        if (!$summaryReport) {
            return response()->json(['message' => 'Report not found or unauthorized access.'], 403);
        }

        $summaryReport->restore();

        return response()->json(['message' => 'Summary report restored successfully!']);
    }
}