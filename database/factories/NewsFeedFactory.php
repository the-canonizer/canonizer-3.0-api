<?php

namespace Database\Factories;

use App\Models\NewsFeed;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFeedFactory extends Factory
{
    protected $model = NewsFeed::class;

    public function definition(): array
    {
        return [
            'display_text' => $this->faker->sentence,
            'link' => $this->faker->url,
            'available_for_child' => 1,
            'submitter_nick_id' => 1,
            'topic_num' => 1,
            'camp_num' => 1,
            'submit_time' => time(),
        ];
    }
}
