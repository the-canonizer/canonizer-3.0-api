<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use App\Models\Namespaces;
use App\Models\Topic;
use App\Models\User;
use App\Models\Nickname;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders([
            'Accept' => 'application/json',
        ]);

        // Seed a default namespace
        if (!Namespaces::where('id', 1)->exists()) {
            DB::table('namespace')->insert([
                'id' => 1,
                'parent_id' => 0,
                'name' => 'main',
                'label' => 'Main',
            ]);
        }

        // Seed a default topic for tests that expect topic_num 1 or topic_id 1
        if (!Topic::where('id', 1)->exists()) {
            DB::table('topic')->insert([
                'id' => 1,
                'topic_num' => 1,
                'topic_name' => 'Agreement',
                'namespace_id' => 1,
                'submitter_nick_id' => 347,
                'go_live_time' => time(),
                'submit_time' => time(),
                'language' => 'English',
                'grace_period' => 0,
            ]);
        }

        // Seed the user and nickname used in ManageTopicApiTest
        $userId = trans('testSample.user_ids.normal_user.user_1');
        if (!User::where('id', $userId)->exists()) {
            DB::table('users')->insert([
                'id' => $userId,
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'testuser@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        if (!Nickname::where('id', 347)->exists()) {
            DB::table('nick_name')->insert([
                'id' => 347,
                'user_id' => $userId,
                'nick_name' => 'TestNickname',
                'private' => 0,
                'default' => 1,
                'create_time' => time()
            ]);
        }
    }
}
