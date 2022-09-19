<?php

namespace Database\Seeders;

use App\Models\VideoPodcast;
use Illuminate\Database\Seeder;

class UpdateVideoPodCastContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $htmlContent = "<div class='col-1'> <h3>Help us bring the world together by canonizing what you believe is right.</h3> <div class='ratio ratio-16x9 mb-4'> <iframe width='560' height='315' src='https://player.vimeo.com/video/728133220?h=25c81d5c91' title='YouTube video player' frameborder='0' allow='accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe> </div> </div> <div class='col-2'> <h2>What's New at Canonizer</h2> <p>Introducing It's <a href=''>Not a Hard Problem; It's a Color Problem,</a> the new video that outlines the emerging consensus around the <a href=''>Representational Qualia Theory</a> that is revolutionizing how we understand human consciousness.</p> <div class='text-center mt-3'> <img src='/color-problem.png' alt='' /> </div> <p>New chapters will be added as they are completed. <a href=''>Check it out!</a></p> </div>";
        $home_page_video_url = 'https://player.vimeo.com/video/728133220?h=25c81d5c91';
        VideoPodCast::where("id", 1)
            ->update([
                'html_content' => $htmlContent,
                'home_page_video_url' => $home_page_video_url
            ]);
    }
}
