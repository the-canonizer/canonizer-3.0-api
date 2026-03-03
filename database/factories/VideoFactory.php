<?php

namespace Database\Factories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'link' => $this->faker->url,
            'thumbnail' => $this->faker->imageUrl(),
            'extension' => 'mp4',
        ];
    }
}
