<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Camp;

class CampArchiveTest extends TestCase
{
    use DatabaseTransactions;

    public function testArchiveCampApiWithoutUserAuth()
    {
        print sprintf("Test without auth  %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/manage-camp', []);
        $_res->assertStatus(401);
    }

    public function testArchiveCampApiWithInvalidData()
    {
        print sprintf("Test with invalid data  %d %s", 400, PHP_EOL);
        $user = User::factory()->make();
        $_res = $this->actingAs($user)->post('/api/v3/manage-camp');
        $_res->assertStatus(400);
    }

    public function testArchiveCampWithValiddata()
    {
        $validData = [
            "topic_num" => 534,
            "camp_num"=>7,
            "nick_name"=>347, 
            "submitter"=>347, 
            "event_type"=>"update",
            "camp_id"=>3377,
            "camp_name"=>"camp 5",
            "parent_camp_num"=>4,
            "is_archive"=>1,

        ];
        print sprintf("Archive camp with valid values ");
        $user = User::factory()->make([
            'id' => '362',
        ]);
        $_res = $this->actingAs($user)->post('/api/v3/manage-camp', $validData);
        $response = $_res->getData();
        if($response->status_code == 200 && $response->data->is_archive ==1 ){
            $_res->assertStatus(200);
        }

    }

}
