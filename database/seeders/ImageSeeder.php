<?php

namespace Database\Seeders;

use App\Models\Image;
use Illuminate\Database\Seeder;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $images = [
            [
                'id' => 1,
                'page_id' => 1,
                'title' => '1st image',
                'description' => 'Home page 1st image',
                'route' => 'home',
                'url' => 'http://localhost/home/public/home/1.png'
            ],
            [
                'id' => 2,
                'page_id' => 2,
                'title' => '2nd image',
                'description' => 'Browse page 1st image',
                'route' => 'browse',
                'url' => 'http://localhost/browse/public/browse/1.png'
            ]
        ];
        
        foreach ($images as $image) {
            Image::updateOrCreate(['id' => $image['id']], $image);
        }
    }
}
