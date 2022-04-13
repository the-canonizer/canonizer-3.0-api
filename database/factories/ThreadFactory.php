<?php

namespace Database\Factories;

use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThreadFactory extends Factory
{
    protected $model = Thread::class;

    public function definition(): array
    {
        return [
            "title" => "Test 3",
            "nick_name" => "449",
            "camp_num" => "1",
            "topic_num" => "290",
            "topic_name" => "Saurabh singh te11s111t 142"
        ];
    }
}
