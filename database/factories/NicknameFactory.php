<?php

namespace Database\Factories;

use App\Models\Nickname;
use Illuminate\Database\Eloquent\Factories\Factory;

class NicknameFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Nickname::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => 1,
            'nick_name' => $this->faker->unique()->userName,
            'private' => 0,
            'default' => 0,
            'create_time' => time(),
        ];
    }
}
