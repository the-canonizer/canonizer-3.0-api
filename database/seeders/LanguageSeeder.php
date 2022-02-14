<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $languages = array(
            'English',
            'French',
            'Hindi',
            'Spanish'
        );

        foreach($languages as $lang){
            DB::table('languages')->insert([
                [                    
                    'name' =>  $lang
                ],
            ]);
        }
    }
}
