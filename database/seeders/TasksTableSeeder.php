<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;

class TasksTableSeeder extends Seeder
{
    public function run()
    {
        $user1 = User::where('name', 'Cheese')->first();
        $user2 = User::where('name', 'Huy')->first();
        $user3 = User::where('name', 'Heotest')->first();
        
        $projects = Project::all();

        if ($user1 && $user2 && $user3 && $projects->count()) {
            foreach ($projects as $project) {
                $tasks = [];

                switch ($project->project_name) {
                    case 'CRM System Development':
                        $tasks = [
                            ['Thu thập yêu cầu khách hàng', 'to-do', 0, 10],
                            ['Thiết kế giao diện & UX', 'in-progress', 5, 20],
                            ['Xây dựng kiến trúc backend', 'to-do', 10, 30],
                            ['Tích hợp API bên thứ ba', 'to-do', 20, 40],
                            ['Phát triển module báo cáo', 'to-do', 30, 50],
                            ['Kiểm thử hệ thống', 'in-progress', 45, 60],
                            ['Triển khai hệ thống', 'to-do', 55, 70],
                            ['Bảo trì & hỗ trợ', 'to-do', 65, 90],
                        ];
                        break;
                    
                    case 'Company Website Redesign':
                        $tasks = [
                            ['Phân tích website cũ & thu thập phản hồi', 'done', 0, 5],
                            ['Thiết kế giao diện mới', 'in-progress', 5, 15],
                            ['Lập trình HTML, CSS, JavaScript', 'to-do', 10, 25],
                            ['Tối ưu hiệu suất trang web', 'to-do', 20, 35],
                            ['Tích hợp CMS', 'to-do', 30, 40],
                            ['Kiểm thử trên nhiều trình duyệt', 'in-progress', 35, 50],
                            ['Triển khai trên server chính', 'to-do', 45, 55],
                            ['Giám sát & sửa lỗi', 'to-do', 50, 60],
                        ];
                        break;
                    
                    case 'E-commerce Platform':
                        $tasks = [
                            ['Thiết kế cơ sở dữ liệu', 'done', 0, 15],
                            ['Tích hợp cổng thanh toán', 'to-do', 10, 30],
                            ['Phát triển hệ thống giỏ hàng', 'to-do', 20, 40],
                            ['Tạo module quản lý sản phẩm', 'to-do', 30, 50],
                            ['Xây dựng hệ thống đánh giá sản phẩm', 'to-do', 40, 60],
                            ['Tối ưu SEO & tìm kiếm sản phẩm', 'in-progress', 50, 70],
                            ['Kiểm thử & tối ưu', 'to-do', 60, 90],
                            ['Triển khai hệ thống', 'to-do', 80, 120],
                        ];
                        break;
                    
                    case 'AI Research Project':
                        $tasks = [
                            ['Thu thập & xử lý dữ liệu', 'done', 0, 20],
                            ['Huấn luyện mô hình AI', 'in-progress', 20, 60],
                            ['Cải tiến thuật toán AI', 'to-do', 50, 80],
                            ['Đánh giá hiệu suất mô hình', 'to-do', 70, 100],
                            ['Viết báo cáo nghiên cứu', 'to-do', 90, 120],
                            ['Chuẩn bị bài thuyết trình', 'to-do', 110, 130],
                            ['Xuất bản báo cáo & công bố kết quả', 'to-do', 120, 150],
                            ['Tối ưu & mở rộng nghiên cứu', 'to-do', 140, 180],
                        ];
                        break;
                    
                    case 'Student Grade Tracking System':
                        $tasks = [
                            ['Thu thập yêu cầu hệ thống', 'done', 0, 10],
                            ['Thiết kế cơ sở dữ liệu', 'to-do', 5, 20],
                            ['Phát triển giao diện người dùng', 'to-do', 15, 30],
                            ['Tích hợp hệ thống điểm số', 'in-progress', 25, 45],
                            ['Kiểm thử & triển khai', 'to-do', 40, 70],
                        ];
                        break;
                    case 'Library Automation':
                        $tasks = [
                            ['Phân tích quy trình mượn/trả sách', 'done', 0, 10],
                            ['Thiết kế hệ thống quản lý sách', 'to-do', 5, 20],
                            ['Phát triển module kiểm kê', 'to-do', 15, 30],
                            ['Tích hợp RFID cho sách', 'in-progress', 25, 45],
                            ['Kiểm thử & tối ưu hóa', 'to-do', 40, 60],
                        ];
                        break;
                    case 'University Mobile App':
                        $tasks = [
                            ['Nghiên cứu nhu cầu sinh viên', 'done', 0, 10],
                            ['Thiết kế giao diện ứng dụng', 'to-do', 5, 20],
                            ['Phát triển chức năng đăng nhập', 'to-do', 15, 30],
                            ['Tích hợp lịch học & thông báo', 'in-progress', 25, 45],
                            ['Kiểm thử trên nhiều thiết bị', 'to-do', 40, 80],
                        ];
                        break;
                    default:
                        continue 2; // Bỏ qua dự án này nếu không có task nào khớp
                }

                foreach ($tasks as $index => $task) {
                    $user_change = [$user1, $user2, $user3][$index % 3]->id;
                    Task::create([
                        'project_id' => $project->project_id,
                        'task_name' => $task[0],
                        'task_description' => "Mô tả cho {$task[0]}",
                        'status_key' => $task[1],
                        'assigned_to_user_id' => $user_change,
                        'in_charge_user_id' => $user_change,
                        'priority' => 'high',
                        'time_start' => Carbon::parse($project->start_date)->addDays($task[2]),
                        'deadline' => Carbon::parse($project->start_date)->addDays($task[3]),
                    ]);
                }
            }
        }
    }
}
