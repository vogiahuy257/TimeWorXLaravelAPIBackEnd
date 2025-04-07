<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;  // Đảm bảo đã import model User

class MeetingsTableSeeder extends Seeder
{
    /**
     * Seed the 'meetings' table.
     *
     * @return void
     */
    public function run()
    {
        // Lấy user_id của user 1 và user 2
        $user1 = User::where('name', 'Cheese')->first();
        $user2 = User::where('name', 'Huy')->first();
    
        if ($user1 && $user2) {
            DB::table('meetings')->insert([
                [
                    'meeting_name' => 'Kickoff Meeting',
                    'meeting_description' => 'Initial meeting for project kickoff.',
                    'meeting_date' => '2024-01-10',
                    'meeting_time' => '10:00:00',
                    'created_by_user_id' => $user1->id,
                    'meeting_type' => 'Project',
                ],
                [
                    'meeting_name' => 'Weekly Standup',
                    'meeting_description' => 'Weekly team standup meeting.',
                    'meeting_date' => '2024-01-17',
                    'meeting_time' => '09:00:00',
                    'created_by_user_id' => $user2->id,
                    'meeting_type' => 'Team',
                ],
                [
                    'meeting_name' => 'Client Sync-up',
                    'meeting_description' => 'Biweekly sync with the client.',
                    'meeting_date' => '2024-01-12',
                    'meeting_time' => '14:00:00',
                    'created_by_user_id' => $user1->id,
                    'meeting_type' => 'Client',
                ],
                [
                    'meeting_name' => 'Product Demo',
                    'meeting_description' => 'Demonstration of the new product features.',
                    'meeting_date' => '2024-01-14',
                    'meeting_time' => '15:30:00',
                    'created_by_user_id' => $user2->id,
                    'meeting_type' => 'Product',
                ],
                [
                    'meeting_name' => 'Planning Session',
                    'meeting_description' => 'Planning for next sprint.',
                    'meeting_date' => '2024-01-16',
                    'meeting_time' => '11:00:00',
                    'created_by_user_id' => $user1->id,
                    'meeting_type' => 'Sprint',
                ],
                [
                    'meeting_name' => 'Retrospective',
                    'meeting_description' => 'Discuss what went well and what to improve.',
                    'meeting_date' => '2024-01-18',
                    'meeting_time' => '16:00:00',
                    'created_by_user_id' => $user2->id,
                    'meeting_type' => 'Sprint',
                ],
                [
                    'meeting_name' => 'Design Review',
                    'meeting_description' => 'Review UI/UX designs for new features.',
                    'meeting_date' => '2024-01-20',
                    'meeting_time' => '13:00:00',
                    'created_by_user_id' => $user1->id,
                    'meeting_type' => 'Design',
                ],
                [
                    'meeting_name' => 'Technical Sync',
                    'meeting_description' => 'Align backend and frontend implementations.',
                    'meeting_date' => '2024-01-22',
                    'meeting_time' => '10:30:00',
                    'created_by_user_id' => $user2->id,
                    'meeting_type' => 'Technical',
                ],
                [
                    'meeting_name' => 'Risk Assessment',
                    'meeting_description' => 'Identify and evaluate potential project risks.',
                    'meeting_date' => '2024-01-24',
                    'meeting_time' => '09:45:00',
                    'created_by_user_id' => $user1->id,
                    'meeting_type' => 'Management',
                ],
                [
                    'meeting_name' => 'Internal Training',
                    'meeting_description' => 'Train new team members.',
                    'meeting_date' => '2024-01-26',
                    'meeting_time' => '14:30:00',
                    'created_by_user_id' => $user2->id,
                    'meeting_type' => 'Training',
                ],
                [
                    'meeting_name' => 'System Upgrade Briefing',
                    'meeting_description' => 'Discuss upcoming server and system upgrades.',
                    'meeting_date' => '2024-01-28',
                    'meeting_time' => '17:00:00',
                    'created_by_user_id' => $user1->id,
                    'meeting_type' => 'Infrastructure',
                ],
                [
                    'meeting_name' => 'Q&A Session',
                    'meeting_description' => 'Open Q&A with stakeholders.',
                    'meeting_date' => '2024-01-30',
                    'meeting_time' => '12:00:00',
                    'created_by_user_id' => $user2->id,
                    'meeting_type' => 'Stakeholder',
                ],
            ]);
        }
    }
    
}
