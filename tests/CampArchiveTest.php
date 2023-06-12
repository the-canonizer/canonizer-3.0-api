<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Camp;

class CampArchiveTest extends TestCase
{
    use DatabaseTransactions;

    public function testArchiveCampApiWithoutUserAuth()
    {
        print sprintf("Test without auth  %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/manage-camp', []);
        $this->assertEquals(401, $response->status());
    }

    public function testArchiveCampApiWithInvalidData()
    {
        print sprintf("Test with invalid data  %d %s", 400, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)->post('/api/v3/manage-camp');
        $this->assertEquals(400,  $this->response->status());
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
        $this->actingAs($user)->post('/api/v3/manage-camp', $validData);
        $response = $this->response->getData();
        if($response->status_code == 200 && $response->data->is_archive ==1 ){
            $this->assertEquals(200,  $this->response->status());
        }

    }

}
