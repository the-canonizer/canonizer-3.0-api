<?php

namespace Database\Seeders;

use App\Models\MetaTag;
use Illuminate\Database\Seeder;

class MetaTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MetaTag::truncate();
        $data = [
            [
                'page_name' => 'home',
                'title' => 'Canonizer',
                'description' => 'Short description',
                'route' => '/',
                'image_url' => 'https://canonizer3.canonizer.com/color-problem.png',
                'keywords' => ['canonizer', 'home-page'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'aboutuspage',
                'title' => 'Canonizer',
                'description' => 'Short description',
                'route' => 'about',
                'image_url' => null,
                'keywords' => ['canonizer', 'about_us', 'about-us'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'treespage',
                'title' => 'Canonizer',
                'description' => 'Short description',
                'route' => 'trees',
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'forgotpasswordpage',
                'title' => 'Canonizer',
                'description' => 'forgot canonizer login password',
                'route' => 'forgot-password',
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'loginpage',
                'title' => 'Canonizer',
                'description' => 'login canonizer',
                'route' => 'login',
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'registrationpage',
                'title' => 'Canonizer',
                'description' => 'Register to canonizer',
                'route' => 'registration',
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'resetpasswordpage',
                'title' => 'Canonizer',
                'description' => 'Reset canonizer login password',
                'route' => 'reset-password',
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'settings',
                'title' => 'Canonizer',
                'description' => 'Account Settings',
                'route' => 'settings',
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'default',
                'title' => 'Canonizer',
                'description' => 'Default short description for canonizer app',
                'route' => '/',
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    
        foreach ($data as $value) {
            MetaTag::create($value);
        }
    }
}
