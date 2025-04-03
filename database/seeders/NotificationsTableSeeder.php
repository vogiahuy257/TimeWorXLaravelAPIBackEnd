<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class NotificationsTableSeeder extends Seeder
{
    /**
     * Seed the 'notifications' table.
     *
     * @return void
     */
    public function run()
    {
        // Lấy user_id của user 'Huy'
        $userHuy = User::where('name', 'Huy')->first();

        // Kiểm tra xem userHuy có tồn tại không
        if ($userHuy) {
            // Tạo thông báo với nhiều loại khác nhau
            $notifications = [
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'info',
                    'message' => 'Bạn đã được thêm vào dự án ABC.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'info',
                    'message' => 'Bạn được giao một task mới: Thiết kế UI.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'success',
                    'message' => 'Task "Thiết kế UI" đã được quản lý phê duyệt.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'success',
                    'message' => 'Task "Phát triển Backend" đã được quản lý phê duyệt.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'warning',
                    'message' => 'Task "Viết tài liệu API" của bạn sắp đến hạn.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'warning',
                    'message' => 'Task "Tối ưu truy vấn DB" của bạn sắp đến hạn.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'error',
                    'message' => 'Task "Kiểm thử bảo mật" của bạn không được phê duyệt.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'error',
                    'message' => 'Dự án ABC có 3 task bị trễ deadline.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'error',
                    'message' => 'Task "Viết báo cáo tổng kết" của bạn không được phê duyệt.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
                [
                    'user_id' => $userHuy->id,
                    'notification_type' => 'warning',
                    'message' => 'Task "Cấu hình server" của bạn sắp đến hạn.',
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null
                ],
            ];

            // Dùng Eloquent để thêm các thông báo
            DB::table('notifications')->insert($notifications);
        }
    }
}
// Để chạy seeder này, bạn có thể sử dụng lệnh sau trong terminal:
// php artisan db:seed --class=NotificationsTableSeeder
// Hoặc bạn có thể thêm nó vào DatabaseSeeder.php để tự động chạy khi chạy tất cả các seeder:
// $this->call(NotificationsTableSeeder::class);
// Sau khi chạy seeder, bạn có thể kiểm tra bảng notifications trong cơ sở dữ liệu để xem các thông báo đã được thêm thành công hay chưa.
