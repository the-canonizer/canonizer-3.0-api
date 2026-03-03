<?php

namespace Database\Factories;

use App\Models\Reply;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReplyFactory extends Factory
{
    protected $model = Reply::class;

    public function definition(): array
    {
        return [
            "body" => "Test post body content",
            "user_id" => 1,
            "c_thread_id" => 1,
            "is_delete" => 0,
        ];
    }
}
