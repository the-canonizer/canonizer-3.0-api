<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            PersonSeeder::class,
            SocialMediaLinksSeeder::class,
            MobileCarrierSeeder::class,
            UpdateVideoPodCastContentSeeder::class,
            AlgorithmSeeder::class,
            LanguageSeeder::class,
            NameSpaceSeeder::class,
            PagesSeeder::class,
            AdsSeeder::class,
            ImageSeeder::class,
            CountrySeeder::class,
        ]);
    }
}
