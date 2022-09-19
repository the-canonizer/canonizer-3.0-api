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
            'nick_name' => 'test',
            'camp_name' => 'test',
            'camp_about_url' => '',
            'parent_camp_num' => '',
            'asof' => ''
        ];
    }
}
