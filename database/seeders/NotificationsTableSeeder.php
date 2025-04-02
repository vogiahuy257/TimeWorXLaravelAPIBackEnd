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
            // Tạo 10 thông báo cho user Huy
            $notifications = [];
            for ($i = 1; $i <= 10; $i++) {
                $notifications[] = [
                    'user_id' => $userHuy->id,  // user_id từ userHuy
                    'notification_type' => 'info',  // Có thể thay đổi kiểu thông báo theo nhu cầu
                    'message' => "Thông báo số {$i} cho Huy.",
                    'notification_date' => now(),
                    'read_status' => false,
                    'link' => null,  // Thêm link thông báo, có thể thay đổi theo yêu cầu
                ];
            }

            // Dùng Eloquent để thêm các thông báo
            DB::table('notifications')->insert($notifications);
        }
    }
}
