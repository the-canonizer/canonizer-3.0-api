<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Activitylog\Models\Activity;

class ActivitiesDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Activity::updateOrCreate(['id' => 26], [
            "log_name" => "topics",
            "description" => "A record has been updated",
            "subject_type" => "App\\Models\\Languages",
            "subject_id" => 18,
            "causer_type" => null,
            "causer_id" => null,
            "properties" => [],
            "created_at" => "2022-02-17T11:22:36.000000Z",
            "updated_at" => "2022-02-17T11:22:36.000000Z",
        ]);
    }
}
