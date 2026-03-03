<?php

namespace Database\Factories;

use App\Models\Statement;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatementFactory extends Factory
{
    protected $model = Statement::class;

    public function definition(): array
    {
        return [
            'topic_num' => 1,
            'camp_num' => 1,
            'value' => $this->faker->paragraph,
            'parsed_value' => $this->faker->paragraph,
            'submit_time' => time(),
            'submitter_nick_id' => 1,
            'go_live_time' => time(),
            'note' => $this->faker->sentence,
            'grace_period' => 0,
            'is_draft' => 0,
        ];
    }
}
