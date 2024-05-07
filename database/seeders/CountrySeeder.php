<?php

namespace Database\Seeders;

use App\Models\Country;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Country::truncate();
  
        $json = File::get("database/data/country.json");
        $countries = json_decode($json);
  
        foreach ($countries as $key => $value) {
            Country::create([
                "phone_code" => $value->phone_code,
                "country_code" => $value->country_code,
                "name" => $value->name,
                "alpha_3" => $value->alpha_3,
            ]);
        }
    }
}
