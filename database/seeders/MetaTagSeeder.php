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
                'page_name' => 'Home',
                'title' => 'Canonizer',
                'is_static' => 1,
                'description' => 'Short description',
                'submitter_nick_id' => null,
                'image_url' => 'https://canonizer3.canonizer.com/color-problem.png',
                'keywords' => ['canonizer', 'home-page'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'AboutusPage',
                'title' => 'Canonizer',
                'is_static' => 1,
                'description' => 'Short description',
                'submitter_nick_id' => null,
                'image_url' => null,
                'keywords' => ['canonizer', 'about_us', 'about-us'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'TreesPage',
                'title' => 'Canonizer',
                'is_static' => 1,
                'description' => 'Short description',
                'submitter_nick_id' => null,
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'ForgotPasswordPage',
                'title' => 'Canonizer',
                'is_static' => 1,
                'description' => 'forgot canonizer login password',
                'submitter_nick_id' => null,
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'LoginPage',
                'title' => 'Canonizer',
                'is_static' => 1,
                'description' => 'login canonizer',
                'submitter_nick_id' => null,
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'RegistrationPage',
                'title' => 'Canonizer',
                'is_static' => 1,
                'description' => 'Register to canonizer',
                'submitter_nick_id' => null,
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'ResetPasswordPage',
                'title' => 'Canonizer',
                'is_static' => 1,
                'description' => 'Reset canonizer login password',
                'submitter_nick_id' => null,
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'Settings',
                'title' => 'Canonizer',
                'is_static' => 1,
                'description' => 'Account Settings',
                'submitter_nick_id' => null,
                'image_url' => null,
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'TopicDetail',
                'title' => 'Canonizer | Topics | Topic Details',
                'is_static' => 0,
                'description' => null,
                'submitter_nick_id' => null,
                'image_url' => 'https://canonizer3.canonizer.com/color-problem.png',
                'keywords' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'page_name' => 'Default',
                'title' => 'Canonizer',
                'is_static' => 1,
                'description' => 'Default short description for canonizer app',
                'submitter_nick_id' => null,
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
