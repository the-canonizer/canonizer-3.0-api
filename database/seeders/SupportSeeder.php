<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class SupportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $support = array(

            [
                'nick_name_id' => 1,
                'delegate_nick_name_id' => 0,
                'topic_num' => 1,
                'camp_num'  =>  2,
                'support_order' => 1,
            ],

            [
                'nick_name_id' => 1,
                'delegate_nick_name_id' => 0,
                'topic_num' => 1,
                'camp_num'  =>  3,
                'support_order' =>  2,
            ],

            [
                'nick_name_id' => 1,
                'delegate_nick_name_id' => 2,
                'topic_num' => 2,
                'camp_num'  =>  1,
                'support_order' =>  1
            ],

        );

        foreach($support as $sp){
            DB::table('support')->insert([
                [
                    'nick_name_id' => $sp['nick_name_id'],
                    'delegate_nick_name_id' => $sp['delegate_nick_name_id'],
                    'topic_num'=> $sp['topic_num'],
                    'camp_num' => $sp['camp_num'],
                    'support_order' => $sp['support_order'],
                    'start' => time(),
                    'end' => 0,
                ],
            ]);
        }
    }
}
