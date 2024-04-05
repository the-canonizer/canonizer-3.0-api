<?php

namespace Database\Seeders;

use App\Models\Ads;
use Illuminate\Database\Seeder;

class AdsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ads = [
            [
                'id' => 1,
                'client_id' => 'ca-pub-6971863585610170',
                'page_id' => 1,
                'slot' => '4564205621',
                'format' => 'auto'
            ],
            [
                'id' => 2,
                'client_id' => 'ca-pub-6971863585610170',
                'page_id' => 2,
                'slot' => '4564205621',
                'format' => 'auto'
            ]
        ];
        
        foreach ($ads as $ad) {
            Ads::updateOrCreate(['id' => $ad['id']], $ad);
        }
    }
}
