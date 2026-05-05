<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TopicCategorySeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['name' => 'Philosophy',              'description' => 'Mind, ethics, metaphysics, meaning'],
            ['name' => 'Politics & Government',   'description' => 'Policy, parties, elections, governance'],
            ['name' => 'Science & Nature',        'description' => 'Empirical questions about the natural world'],
            ['name' => 'Technology',              'description' => 'Computing, AI, engineering, the internet'],
            ['name' => 'Health & Medicine',       'description' => 'Medicine, public health, wellness'],
            ['name' => 'Culture & Society',       'description' => 'Arts, identity, social norms, history'],
            ['name' => 'Economics & Finance',     'description' => 'Markets, money, work, trade'],
            ['name' => 'Education',               'description' => 'Teaching, learning, schools'],
            ['name' => 'Religion & Spirituality', 'description' => 'Faith, theology, religious practice'],
            ['name' => 'Sports & Recreation',     'description' => 'Sports, games, hobbies, leisure'],
        ];
        foreach ($rows as $row) {
            DB::table('topic_categories')->updateOrInsert(
                ['name' => $row['name']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
