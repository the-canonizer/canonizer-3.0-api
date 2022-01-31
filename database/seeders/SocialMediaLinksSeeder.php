<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SocialMediaLink;

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
            ),
            array(
                'label' => 'Instagram',
                'link' => 'https://www.instagram.com/',
                'icon' => '/images/social-media/instagram.svg',
            ),
            array(
                'label' => 'Twitter',
                'link' => 'https://www.twitter.com/',
                'icon' => '/images/social-media/twitter.svg',
            ),
            array(
                'label' => 'Youtube',
                'link' => 'https://www.youtube.com/',
                'icon' => '/images/social-media/youtube.svg',
            ),
            array(
                'label' => 'LinkedIn',
                'link' => 'https://www.linkedin.com/',
                'icon' => '/images/social-media/linkedIn.svg',
            ),
        );

        foreach ($socialMediasArr as $key => $value) {
            SocialMediaLink::updateOrCreate($value, $value);
        }
    }
}
