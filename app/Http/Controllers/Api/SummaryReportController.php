<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SummaryReport;
use Illuminate\Http\Request;
use App\Services\ReportZipper;
use Illuminate\Support\Facades\DB;

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
            $validated = $request->validated();
            $userId = $request->user()->id;
            $validated['reported_by_user_id'] = $userId;
            $reportFiles = $validated['report_files'] ?? [];

            if (!is_array($validated['report_files'])) {
                return response()->json(['message' => 'Invalid format for report files.'], 400);
            }

            return DB::transaction(function () use ($validated, $reportFiles) {
                $zipFileName = null;
                $zipFilePath = null;
    
                // 📌 Chỉ tạo file ZIP nếu có tài liệu đính kèm
                if (!empty($reportFiles)) {
                    $zipFileName = 'summary_report_' . time() . '.zip';
                    $zipFilePath = $this->zipper->createZip($zipFileName, $reportFiles);
                }
    
                $summaryReport = SummaryReport::create([
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'report_date' => $validated['report_date'],
                    'project_id' => $validated['project_id'] ?? null,
                    'project_name' => $validated['project_name'] ?? null,
                    'project_description' => $validated['project_description'] ?? null,
                    'reported_by_user_id' => $validated['reported_by_user_id'],
                    'zip_file_path' => $zipFilePath,
                    'zip_name' => $zipFileName,
                ]);
    
                return response()->json([
                    'message' => 'Summary report created successfully!',
                    'summary_report' => $summaryReport
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Error creating summary report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create summary report.', 'error' => $e->getMessage()], 500);
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
    public function getSummaryReportById(Request $request, int $id)
    {
        $userId = $request->user()->id;
        
        $summaryReport = SummaryReport::where('id', $id)
            ->where('reported_by_user_id', $userId)
            ->first();

        if (!$summaryReport) {
            return response()->json(['message' => 'Report not found or unauthorized access.'], 403);
        }

        return response()->json(['message' => 'Summary report fetched successfully!', 'summary_report' => $summaryReport]);
    }

    /**
     * API để tải file ZIP của báo cáo tổng hợp.
     */
    public function downloadSummaryReportZip(Request $request, int $id)
    {
        $userId = $request->user()->id;

        $summaryReport = SummaryReport::where('id', $id)
            ->where('reported_by_user_id', $userId)
            ->first();

        if (!$summaryReport || !$summaryReport->zip_file_path) {
            return response()->json(['message' => 'Report or ZIP file not found.'], 403);
        }

        $filePath = storage_path('app/' . $summaryReport->zip_file_path);

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'ZIP file does not exist.'], 404);
        }

        return response()->download($filePath, $summaryReport->zip_name);
    }

    /**
     * Xóa mềm (đưa vào thùng rác)
     */
    public function softDeleteSummaryReport(Request $request, int $id)
    {
        $userId = $request->user()->id;

        $summaryReport = SummaryReport::where('id', $id)
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
            ->where('id', $id)
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
}