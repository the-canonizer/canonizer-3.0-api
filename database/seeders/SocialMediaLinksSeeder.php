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
                'link' => 'https://www.facebook.com/',
                'icon' => 'fa fa-facebook',
            ),
            array(
                'label' => 'Twitter',
                'link' => 'https://www.twitter.com/',
                'icon' => 'fa fa-twitter',
            ),
            array(
                'label' => 'Youtube',
                'link' => 'https://www.youtube.com/',
                'icon' => 'fa fa-youtube',
            ),
        );

        foreach ($socialMediasArr as $key => $value) {
            SocialMediaLink::updateOrCreate($value, $value);
        }
    }
}
