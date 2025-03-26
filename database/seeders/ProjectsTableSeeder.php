<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

class ProjectsTableSeeder extends Seeder
{
    public function run()
    {
        $user = User::where('name', 'Huy')->first(); 

        if ($user) {
            $projectManagerId = $user->id;

            $projects = [
                ['CRM System Development', 'Xây dựng hệ thống CRM quản lý khách hàng.', 'High', 'in-progress', 0, 90],
                ['Company Website Redesign', 'Thiết kế lại website công ty.', 'Medium', 'verify', 0, 60],
                ['E-commerce Platform', 'Xây dựng nền tảng TMĐT bán hàng.', 'High', 'to-do', 0, 120],
                ['HR Management System', 'Triển khai hệ thống quản lý nhân sự.', 'Medium', 'done', 0, 75],
                ['Data Security Enhancement', 'Tăng cường bảo mật dữ liệu.', 'High', 'verify', 0, 50],
                ['Smart Attendance System', 'Điểm danh thông minh bằng AI.', 'Medium', 'done', 0, 90],
                ['Library Automation', 'Tự động hóa mượn/trả sách.', 'Low', 'to-do', 0, 60],
                ['University Mobile App', 'Ứng dụng di động hỗ trợ sinh viên.', 'High', 'in-progress', 0, 80],
                ['AI Research Project', 'Nghiên cứu AI xử lý ngôn ngữ.', 'High', 'verify', 0, 150],
                ['Student Grade Tracking System', 'Theo dõi điểm số sinh viên.', 'Medium', 'done', 0, 70],
            ];

            foreach ($projects as $project) {
                DB::table('projects')->insert([
                    'project_name' => $project[0],
                    'project_description' => $project[1],
                    'start_date' => Carbon::now()->addDays($project[4]),
                    'end_date' => Carbon::now()->addDays($project[5]),
                    'project_priority' => $project[2],
                    'project_status' => $project[3],
                    'project_manager' => $projectManagerId,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        } else {
            echo "User 'Huy' không tồn tại.\n";
        }
    }
}
