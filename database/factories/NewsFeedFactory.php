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
            "id" => rand(20000,90000)
        ];
    }
}
