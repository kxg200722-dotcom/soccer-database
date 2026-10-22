<?php

namespace Database\Seeders;

use App\Enums\GroupType;
use App\Enums\MatchStatus;
use App\Enums\RefereeRole;
use App\Models\MatchInfo;
use App\Models\MatchReferee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@ball.local'],
            ['name' => 'admin', 'password' => 'admin123', 'is_admin' => true],
        );

        // 每组别 3 场比赛：未开始/进行中/已结束 各一
        $teams = [
            GroupType::UNIVERSITY->value => [['A 大学', 'B 大学'], ['C 大学', 'D 大学'], ['E 大学', 'F 大学']],
            GroupType::MIDDLE_SCHOOL->value => [['第一中学', '实验中学'], ['第二中学', '外国语学校'], ['育才中学', '华兴中学']],
            GroupType::PRIMARY_SCHOOL->value => [['阳光小学', '育才小学'], ['实验一小', '实验二小'], ['红星小学', '希望小学']],
        ];
        $statuses = [MatchStatus::NOT_STARTED, MatchStatus::LIVE, MatchStatus::FINISHED];

        foreach ($teams as $groupType => $pairs) {
            foreach ($pairs as $i => [$home, $away]) {
                $status = $statuses[$i];
                $match = MatchInfo::create([
                    'group_type' => $groupType,
                    'home_team_name' => $home,
                    'away_team_name' => $away,
                    'match_time' => now()->addDays($i - 1)->setHour(15)->setMinute(0),
                    'venue' => '市体育中心 '.($i + 1).' 号场',
                    'status' => $status->value,
                    'home_score' => $status === MatchStatus::NOT_STARTED ? 0 : $i,
                    'away_score' => $status === MatchStatus::NOT_STARTED ? 0 : 1,
                    'version' => 0,
                    'created_by' => 1,
                ]);

                MatchReferee::insert([
                    ['match_id' => $match->id, 'role' => RefereeRole::CHIEF->value, 'name' => '张裁判', 'created_at' => now(), 'updated_at' => now()],
                    ['match_id' => $match->id, 'role' => RefereeRole::ASSISTANT->value, 'name' => '李助理', 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
        }
    }
}
