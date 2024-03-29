<?php

namespace Database\Seeders;

use App\Models\Video;
use Illuminate\Database\Seeder;

class VideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Video::truncate();
        $data = [
            [
                'id' => 1,
                'title' => 'Introduction',
                'link' => 'introduction',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'title' => 'Perceiving a Strawberry',
                'link' => 'perceiving_a_strawberry',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'title' => 'Differentiating Reality and Knowledge of Reality',
                'link' => 'differentiate_reality_knowledge',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 4,
                'title' => 'The world in your head',
                'link' => 'The_world_in_you_head',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 5,
                'title' => 'The Perception Of Size',
                'link' => 'The_Perception_Of_Size',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 6,
                'title' => 'Computational Binding',
                'link' => 'computational_binding',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 7,
                'title' => 'Cognitive Knowledge',
                'link' => 'cognitive_knowledge',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 8,
                'title' => 'Simulation Hypothesis',
                'link' => 'simulation_hypothesis',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 9,
                'title' => 'Representational Qualia Theory Consensus',
                'link' => 'representational_qualia_consensus',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 10,
                'title' => 'Conclusion',
                'link' => 'conclusion',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        Video::insert($data);
    }
}
