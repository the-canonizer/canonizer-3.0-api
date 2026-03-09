<?php

namespace Database\Factories;

use App\Models\Camp;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampFactory extends Factory
{
    protected $model = Camp::class;

    public function definition(): array
    {
        return [
            'camp_name' => $this->faker->word,
            'topic_num' => 1,
            'parent_camp_num' => 1,
            'submitter_nick_id' => 1,
            'go_live_time' => time(),
            'proposed' => 0,
            'note' => $this->faker->sentence,
            'key_words' => $this->faker->word,
            'submit_time' => time(),
            'camp_num' => $this->faker->numberBetween(1, 10000),
            'title' => $this->faker->word,
        ];
    }
}
