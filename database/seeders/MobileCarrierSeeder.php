<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MobileCarrierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $carriers = array(
            [
                'carrier_address' => 'sms.alltelwireless.com',
                'name' => 'Alltel'
            ],
            [
                'carrier_address' => 'txt.att.net',
                'name' => 'AT&T'
            ],
            [
                'carrier_address' => 'sms.myboostmobile.com',
                'name' => 'Boost Mobile'
            ],
            [
                'carrier_address' => 'mms.cricketwireless.net',
                'name' => 'Cricket Wireless'
            ],
            [
                'carrier_address' => 'mymetropcs.com',
                'name' => 'MetroPCS'
            ],
            [
                'carrier_address' => 'text.republicwireless.com',
                'name' => 'Republic Wireless'
            ],
            [
                'carrier_address' => 'messaging.sprintpcs.com',
                'name' => 'Sprint'
            ],
            [
                'carrier_address' => 'tmomail.net',
                'name' => 'T-Mobile'
            ],
            [
                'carrier_address' => 'email.uscc.net',
                'name' => 'U.S. Cellular'
            ],
            [
                'carrier_address' => 'vtext.com',
                'name' => 'Verizon Wireless'
            ],
            [
                'carrier_address' => 'vmobl.com',
                'name' => 'Virgin Mobile'
            ],
            [
                'carrier_address' => 'non_usa',
                'name' => 'Non USA'
            ],
        );

        foreach($carriers as $carrier){
            DB::table('mobile_carrier')->insert([
                [
                    'carrier_address' => $carrier['carrier_address'],
                    'name' =>  $carrier['name']
                ],
            ]);
        }
    }
}
