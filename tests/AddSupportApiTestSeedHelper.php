<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use App\Models\Topic;
use App\Models\Camp;
use App\Models\Nickname;

trait AddSupportApiTestSeedHelper
{
    public function seedTestData()
    {
        // 1. Topic 190, Camp 1
        $this->createTopicAndCamp(190, 'Topic 190', 1, 'Agreement');
        // 2. Topic 715, Camp 1
        $this->createTopicAndCamp(715, 'Topic 715', 1, 'Agreement');
        // 3. Topic 173, Camps 1, 2, 3
        $this->createTopicAndCamp(173, 'Software Testing', 1, 'Agreement');
        $this->createTopicAndCamp(173, 'Software Testing', 2, 'Types Of Testing', 1);
        $this->createTopicAndCamp(173, 'Software Testing', 3, 'Levels Of Testing', 1);
        $this->addSupportRecord(173, 3, 347); // User 347 supports Camp 3
        $this->addSupportRecord(173, 2, 347); // User 347 supports Camp 2
        // 4. Topic 735, Camps 1, 2
        $this->createTopicAndCamp(735, 'Topic 735', 1, 'Agreement');
        $this->createTopicAndCamp(735, 'Topic 735', 2, 'Camp 1', 1);
        $this->addSupportRecord(735, 1, 347); // User 347 supports Parent Camp
        // 5. Topic 416, Camp 3
        $this->createTopicAndCamp(416, 'Topic 416', 1, 'Agreement');
        $this->createTopicAndCamp(416, 'Topic 416', 3, 'Camp 3', 1);
        
        // Ensure Delegate Brent_Allsop
        if (!Nickname::where('id', 348)->exists()) {
            DB::table('nick_name')->insert([
                'id' => 348,
                'user_id' => trans('testSample.user_ids.normal_user.user_1'), // same user or different doesn't matter for nick check mostly
                'nick_name' => 'RC',
                'private' => 0,
                'default' => 0,
                'create_time' => time()
            ]);
        }
        $this->addSupportRecord(416, 3, 347, 348); // User 347 delegates to 348
    }

    private function createTopicAndCamp($topicNum, $topicName, $campNum, $campName, $parentCampNum = null)
    {
        if (!Topic::where('topic_num', $topicNum)->exists()) {
            DB::table('topic')->insert([
                'topic_num' => $topicNum,
                'topic_name' => $topicName,
                'namespace_id' => 1,
                'submitter_nick_id' => 347,
                'go_live_time' => time() - 3600,
                'submit_time' => time() - 7200,
                'language' => 'English',
                'grace_period' => 0,
            ]);
        }

        if (!Camp::where('topic_num', $topicNum)->where('camp_num', $campNum)->exists()) {
            DB::table('camp')->insert([
                'topic_num' => $topicNum,
                'camp_num' => $campNum,
                'parent_camp_num' => $parentCampNum,
                'camp_name' => $campName,
                'submitter_nick_id' => 347,
                'go_live_time' => time() - 3600,
                'submit_time' => time() - 7200,
                'language' => 'English',
                'grace_period' => 0,
            ]);
        }
    }

    private function addSupportRecord($topicNum, $campNum, $nickNameId, $delegateNickNameId = 0)
    {
        DB::table('support')->insert([
            'nick_name_id' => $nickNameId,
            'delegate_nick_name_id' => $delegateNickNameId,
            'topic_num' => $topicNum,
            'camp_num' => $campNum,
            'support_order' => 1,
            'start' => time() - 3600,
            'end' => 0,
        ]);
    }
}
