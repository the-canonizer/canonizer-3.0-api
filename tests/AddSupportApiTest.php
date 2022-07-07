<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class AddSupportApiTest extends TestCase
{

    use DatabaseTransactions;
    
  
    public function testWhenSupportDoNotExists()
    {
        print sprintf(" \n Test if support Exists... %d %s", 200, PHP_EOL);
        
        $user = User::factory()->make();
        $this->actingAs($user)->get('/api/v3/support/check?topic_num=724&camp_num=1');
        
        $this->seeJsonEquals([
            'status_code'=>200,
            'error'=>"",
            'message'=>"This camp doesn't have your support",
            "data" => [
                'support_flag' => 0,
            ],
        ]);
        
       /* $data = [
                "status_code" =>  200,
                "message" => "This camp doesnt have your support",
                "error" => "",
                "data" => [
                    "support_flag" => 0
                ]
            ];

            $response->assertJson(fn (AssertableJson $json) =>
                $json->has('status_code')
                     ->etc()
            );*/

        
        //$response->assertJson(json_encode($data), $strict = false);


    }

    /*
    public function testWarningMessageIFSupportSwitchFromParentTochild()
    {

    }

    public function testWarningMessageIfSupportSwitchFromChildToParent()
    {

    }

    public function testWarningMessageWhenDelegatorAddDirectSupport()
    {

    }

    public function testWarningMessageWhenDirectSupporterDelegatesSupport()
    {

    }   

    public function testAddSupportWithValidData()
    {

    }*/
}
