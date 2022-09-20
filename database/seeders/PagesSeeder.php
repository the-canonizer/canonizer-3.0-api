<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pages = [
            ['id' => 1, 'name' => 'Home'],
            ['id' => 2, 'name' => 'Browse'],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['id' => $page['id']], $page);
        }
    }
}
