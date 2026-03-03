<?php

namespace Database\Factories;

use App\Models\Resolution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResolutionFactory extends Factory
{
    protected $model = Resolution::class;

    public function definition(): array
    {
        return [
            'title' => '720p',
            'resolution' => '720p',
        ];
    }
}
