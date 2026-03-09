<?php

namespace Database\Factories;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        return [
            'topic_name' => $this->faker->name,
            'namespace_id' => 1,
            'submitter_nick_id' => 1,
            'go_live_time' => time(),
            'language' => 'English',
            'proposed' => 0,
            'namespace' => 'General',
            'note' => $this->faker->sentence,
            'topic_num' => $this->faker->numberBetween(1000, 90000),
            'submit_time' => time(),
        ];
    }
}
