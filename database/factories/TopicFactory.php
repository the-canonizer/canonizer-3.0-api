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
            'topic_name' => 'test',
            'namespace'=>'12',
            'create_namespace'=>'',
            'nick_name'=>'12',
            'asof'=>''
        ];
    }
}
