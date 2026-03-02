<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders([
            'Accept' => 'application/json',
        ]);

        // Seed a default topic for tests that expect topic_num 1 or topic_id 1
        if (\App\Models\Topic::count() == 0) {
            \App\Models\Topic::factory()->create([
                'id' => 1,
                'topic_num' => 1,
                'topic_name' => 'Agreement',
                'namespace_id' => 1,
                'submitter_nick_id' => 1,
                'go_live_time' => time(),
            ]);
        }
    }
}
