<?php

namespace Database\Factories;

use App\Models\Reply;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReplyFactory extends Factory
{
    protected $model = Reply::class;

    public function definition(): array
    {
        return [
                "body" => "gfgfgfffefef",
                "nick_name" => "449",
                "thread_id" => "465",
                "camp_num" => "1",
                "topic_num" => "290",
                "topic_name" => "Saurabh singh te11s111t 142"
        ];
    }
}
