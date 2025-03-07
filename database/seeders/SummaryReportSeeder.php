<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SummaryReport;
use App\Models\User;
use Carbon\Carbon;

class SummaryReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::where('name', 'Huy')->first();

        if (!$user) {
            $this->command->error('Không tìm thấy user Huy. Hãy kiểm tra lại database.');
            return;
        }

        for ($i = 1; $i <= 12; $i++) {
            SummaryReport::create([
                'project_id' => $i, 
                'project_name' => "Dự án $i",
                'project_description' => "Mô tả về dự án $i - Một dự án giả lập cho testing.",
                'name' => "Sprint $i Summary",
                'reported_by_user_id' => $user->id,
                'report_date' => Carbon::now()->subDays($i), // Mỗi báo cáo lùi lại 1 ngày
                'summary' => "Tổng hợp tiến độ Sprint $i.",
                'completed_tasks' => "Hoàn thành nhiệm vụ trong Sprint $i.",
                'upcoming_tasks' => "Chuẩn bị triển khai Sprint " . ($i + 1) . ".",
                'project_issues' => "Không có vấn đề nào trong Sprint $i.",
                'zip_name' => null,
                'zip_file_path' => null,
            ]);
        }

        $this->command->info('✅ Đã tạo 12 Summary Reports thành công.');
    }
}
