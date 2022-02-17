<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NameSpaceSeeder extends Seeder
{
    /**
     * Run the database seeds for namespace Table
     *
     * @return void
     */
    public function run()
    {
        $namespaces = array(
            [
                'id'        => 1,
                'parent_id' => 0,
                'name'      => 'General',
                'label'       => 'General'
            ],
            [
                'id'        => 2,
                'parent_id' => 0,
                'name'      => 'corporations',
                'label'     => '/corporations/'
            ],
            [
                'id'        => 3,
                'parent_id' => 0,
                'name'      => 'crypto_currency',
                'label'     => '/crypto_currency/'
            ],
            [
                'id'        => 4,
                'parent_id' => 0,
                'name'      => 'family',
                'label'     => '/family/'
            ],
            [
                'id'        => 5,
                'parent_id' => 4,
                'name'      => 'Jesperson_Oscar_F',
                'label'     => '/family/Jesperson_Oscar_F/'
            ],
            [
                'id'        => 6,
                'parent_id' => 0,
                'name'      => 'Occupy Wall Street',
                'label'     => '/Occupy Wall Street/'
            ],
            [
                'id'        => 7,
                'parent_id' => 0,
                'name'      => 'organizations',
                'label'     => '/organizations/'
            ],
            [
                'id'        => 8,
                'parent_id' => 7,
                'name'      => 'canonizer',
                'label'     => '/organizations/canonizer/'
            ],
            [
                'id'        => 9,
                'parent_id' => 8,
                'name'      => 'help',
                'label'     => '/organizations/canonizer/help/'
            ],
            [
                'id'        => 10,
                'parent_id' => 7,
                'name'      => 'mta',
                'label'     => '/organizations/mta/'
            ],
            [
                'id'        => 11,
                'parent_id' => 7,
                'name'      => 'TV07',
                'label'     => '/organizations/TV07/'
            ],
            [
                'id'        => 12,
                'parent_id' => 7,
                'name'      => 'wta',
                'label'     => '/organizations/wta/'
            ],
            [
                'id'        => 13,
                'parent_id' => 0,
                'name'      => 'personal_attributes',
                'label'     => '/personal_attributes/'
            ],
            [
                'id'        => 14,
                'parent_id' => 0,
                'name'      => 'personal_reputations',
                'label'     => '/personal_reputations/'
            ],
            [
                'id'        => 15,
                'parent_id' => 0,
                'name'      => 'professional_services',
                'label'     => '/professional_services/'
            ],
            [
                'id'        => 16,
                'parent_id' => 0,
                'name'      => 'sandbox',
                'label'     => '/sandbox/'
            ],
            [
                'id'        => 17,
                'parent_id' => 0,
                'name'      => 'terminology',
                'label'     => '/terminology/'
            ],
            [
                'id'        => 18,
                'parent_id' => 0,
                'name'      => 'www',
                'label'     => '/www/'
            ],
            [
                'id'        => 19,
                'parent_id' => 0,
                'name'      => 'sandbox testing',
                'label'     => '/sandbox testing/'
            ],
            [
                'id'        => 21,
                'parent_id' => 3,
                'name'      => 'ethereum',
                'label'     => '/crypto_currency/ethereum/'
            ],
            [
                'id'        => 22,
                'parent_id' => 3,
                'name'      => 'void',
                'label'     => '/void/'
            ],
            [
                'id'        => 24,
                'parent_id' => 0,
                'name'      => 'Mormon_Canon_Project',
                'label'     => '/Mormon_Canon_Project/'
            ],
            [
                'id'        => 25,
                'parent_id' => 7,
                'name'      => 'united_utah_party',
                'label'     => '/organizations/united_utah_party/'
            ],
            [
                'id'        => 26,
                'parent_id' => 0,
                'name'      => 'government',
                'label'     => '/government/'
            ],
            [
                'id'        => 27,
                'parent_id' => 26,
                'name'      => 'sandy_city',
                'label'     => '/government/sandy_city/'
            ],
        );

        foreach($namespaces as $ns){
            DB::table('namespace')->insert([
                [
                    'id'        => $ns['id'],
                    'parent_id' => $ns['parent_id'],
                    'name'      => $ns['name'],
                    'label'     => $ns['label']
                ],
            ]);
        }
    }
}
