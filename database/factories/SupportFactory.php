<?php

namespace Database\Factories;

use App\Models\Support;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportFactory extends Factory
{
    protected $model = Support::class;

    public function definition()
    {
        return [
            'nick_name_id' => 1,
            'topic_num' => 1,
            'camp_num' => 1,
            'delegate_nick_name_id' => 0,
            'start' => time(),
            'end' => 0,
            'support_order' => 1,
        ];
    }
}
