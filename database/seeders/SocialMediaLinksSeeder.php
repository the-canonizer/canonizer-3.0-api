<?php

namespace Database\Seeders;

use App\Models\SocialMediaLink;
use Illuminate\Database\Seeder;

class SocialMediaLinksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $socialMediasArr = array(
            array(
                'label' => 'Facebook',
                'link' => 'https://www.facebook.com/pages/Canonizer.com/103927141540408/',
                'icon' => '/images/social-media/facebook.svg',
                'order_number' => 1,
            ),
            array(
                'label' => 'Instagram',
                'link' => 'https://www.instagram.com/',
                'icon' => '/images/social-media/instagram.svg',
                'order_number' => 2,
            ),
            array(
                'label' => 'Twitter',
                'link' => 'https://www.twitter.com/',
                'icon' => '/images/social-media/twitter.svg',
                'order_number' => 3,
            ),
            array(
                'label' => 'Youtube',
                'link' => 'https://www.youtube.com/',
                'icon' => '/images/social-media/youtube.svg',
                'order_number' => 4,
            ),
            array(
                'label' => 'LinkedIn',
                'link' => 'https://www.linkedin.com/',
                'icon' => '/images/social-media/linkedIn.svg',
                'order_number' => 5,
            ),
        );

        foreach ($socialMediasArr as $key => $value) {
            SocialMediaLink::updateOrCreate(['label' => $value['label']], $value);
        }
    }
}
