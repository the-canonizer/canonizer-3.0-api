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
                'thumbnail' => 'introduction_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'title' => 'Perceiving a Strawberry',
                'link' => 'perceiving_a_strawberry',
                'thumbnail' => 'perceiving_a_strawberry_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'title' => 'Differentiating Reality and Knowledge of Reality',
                'link' => 'differentiate_reality_knowledge',
                'thumbnail' => 'differentiate_reality_knowledge_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 4,
                'title' => 'The world in your head',
                'link' => 'The_world_in_you_head',
                'thumbnail' => 'the_world_in_your_head_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 5,
                'title' => 'The Perception Of Size',
                'link' => 'The_Perception_Of_Size',
                'thumbnail' => 'the_perception_of_size_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 6,
                'title' => 'Computational Binding',
                'link' => 'computational_binding',
                'thumbnail' => 'computational_binding_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 7,
                'title' => 'Cognitive Knowledge',
                'link' => 'cognitive_knowledge',
                'thumbnail' => 'cognitive_knowledge_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 8,
                'title' => 'Simulation Hypothesis',
                'link' => 'simulation_hypothesis',
                'thumbnail' => 'simulation_hypothesis_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 9,
                'title' => 'Representational Qualia Theory Consensus',
                'link' => 'representational_qualia_consensus',
                'thumbnail' => 'representational_qualia_consensus_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 10,
                'title' => 'Conclusion',
                'link' => 'conclusion',
                'thumbnail' => 'conclusion_thumb',
                'extension' => 'mp4',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        Video::insert($data);
    }
}
