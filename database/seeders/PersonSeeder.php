<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(['email' => 'test_user@canonizer.com'],[
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => Hash::make('password'),
            'address_1' => '10000 S',
            'city' => 'Sandy',
            'state' => 'Utah',
            'postal_code' => '84092',
            'country' => 'US',
            'update_time' => time(),
            'join_time' => time(),
            'language' => 'English',
            'gender' => 0,
            'private_flags' => 'first_name,middle_name,last_name,email',
            'status' => 1
        ]);
    }
}
