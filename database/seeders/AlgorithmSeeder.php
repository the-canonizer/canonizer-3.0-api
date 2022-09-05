<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Algorithm;

class AlgorithmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $algorithms = array(
            'blind_popularity' => 'One Person One Vote',
            'mind_experts' => 'Mind Experts',
            'computer_science_experts' => 'Computer Science Experts',
            'PhD' => 'Ph.D.',
            'christian' => 'Christian',
            'secular' => 'Secular / Non Religious',
            'mormon' => 'Mormon',
            'uu' => 'Universal Unitarian',
            'atheist' => 'Atheist',
            'transhumanist' => 'Transhumanist',
            'united_utah' => 'United Utah',
            'republican' => 'Republican',
            'forward_party'=>'Forward Party',
            'democrat' => 'Democrat',
            'ether' => 'Ethereum',
            'shares' => 'Canonizer Shares',
            'shares_sqrt' => 'Canonizer Canonizer',
            'sandy_city' => "Sandy City",
            'sandy_city_council' => "Sandy City Council"
        );

        foreach ($algorithms as $key => $value) {
             
             Algorithm::updateOrCreate([
                 'algorithm_key' => $key
             ], [
                 'algorithm_key' => $key,
                 'algorithm_label' => $value
             ]);
        }

    }
}
