<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PassportClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if ID 6 exists, if not create a client credentials client
        $client = DB::table('oauth_clients')->where('id', 6)->first();
        
        if (!$client) {
            DB::table('oauth_clients')->insert([
                'id' => 6,
                'user_id' => null,
                'name' => 'Canonizer3_combined ClientCredentials Grant Client',
                'secret' => '3PZ9Ebef9JMmS9s0mf0AUWO2vcoe94cGSjShEiTQ',
                'provider' => null,
                'redirect' => '',
                'personal_access_client' => 0,
                'password_client' => 0,
                'revoked' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            echo "Client ID 6 created successfully.\n";
        } else {
            echo "Client ID 6 already exists.\n";
        }
    }
}
