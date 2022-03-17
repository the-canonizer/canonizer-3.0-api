<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $namespace = "General";
        $namespaceId = "1";
        $language = "English";
        $topics = array(
            [
                'topic_name' => "Canonizer first topic",
                'topic_num' => 1,
                'namespace'=>$namespace,
                'namespace_id'=>$namespaceId,
                'language'=>$language,
                'submit_time'=>time(),
                'submitter_nick_id'=>1,
                'go_live_time'=> time(),                
                'camp' => array(
                    [
                        'topic_num' => "1",
                        'title' => 'Canonizer first topic',
                        'camp_name' => "Agreement",
                        'camp_num' => "1",
                        'parent_camp_num' => "",
                     ],
                    [
                       'topic_num' => "1",
                       'title' => '',
                       'camp_name' => "First camp of first topic",
                       'camp_num' => "2",
                       'parent_camp_num' => "1",
                    ],
                    [
                        'topic_num' => "1",
                        'title' => '',
                        'camp_name' => "Second camp of first topic",
                        'camp_num' => "3",
                        'parent_camp_num' => "1",
                     ]
                )
            ],
            [
                'topic_name' => "Canonizer second topic",
                'topic_num' => "2",
                'namespace'=>$namespace,
                'namespace_id'=>$namespaceId,
                'language'=>$language,
                'submit_time'=>time(),
                'submitter_nick_id'=>1,
                'go_live_time'=> time(),
                'camp' => array(
                    [
                        'topic_num' => "2",
                        'title' => 'Canonizer second topic',
                        'camp_name' => "Agreement",
                        'camp_num' => "1",
                        'parent_camp_num' => "",
                     ],
                    [
                       'topic_num' => "2",
                       'title' => '',
                       'camp_name' => "First camp of seconf topic",
                       'camp_num' => "2",
                       'parent_camp_num' => "1",
                    ],
                    [
                        'topic_num' => "2",
                        'title' => '',
                        'camp_name' => "Second camp of seconf topic",
                        'camp_num' => "3",
                        'parent_camp_num' => "1",
                     ]
                )
            ],

        );


        foreach($topics as $topic){
            DB::table('topic')->insert([
                [
                    'topic_name' => $topic['topic_name'],
                    'topic_num' => $topic['topic_num'],
                    'namespace'=>$namespace,
                    'namespace_id'=>$namespaceId,
                    'language'=>$language,
                    'submit_time'=>time(),
                    'submitter_nick_id'=>1,
                    'go_live_time'=> time(),
                ],
            ]);

            foreach($topic['camp'] as $child){
                //insert agreement camp in camp
                DB::table('camp')->insert([
                    [
                        'title' => $topic['topic_name'],
                        'camp_name' => $child['camp_name'],
                        'topic_num' => $child['topic_num'],
                        'camp_num' => $child['camp_num'],
                        'language'=>$language,
                        'submit_time'=>time(),
                        'submitter_nick_id'=>1,
                        'go_live_time'=> time(),
                    ],
                ]);
            }

            
        }
    }
}
