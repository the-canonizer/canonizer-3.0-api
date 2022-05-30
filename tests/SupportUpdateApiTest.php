<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\User;

class SupportUpdateApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     *  [unauthorized user is restricted to perform this action]
     *
     * @return void
     */
    public function testUnauthorizedUserCannotUpdateSupportOrder()
    {
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/support-order/update', []);
        $this->assertEquals(401, $response->status());
    }

    /**
     * [unautorized user is restricted to perform this action]
     */
    public function testUnauthorizedUserCannotRemoveSupport()
    {
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/support/remove', []);
        $this->assertEquals(401, $response->status());
    }

    /**
     *  [Remove support with invalid data]
     */
    public function testRemoveSupportWithInvalidData()
    {
        $user = User::factory()->make();
        $data = [];
        $this->actingAs($user)
        ->post('/api/v3/support/remove',$data);
        $this->assertEquals(500, $this->response->status());
    }


    public function testRemoveSupportWithValidaData()
    {
        $user = User::factory()->make();
        $data = [
            "topic_num" => '1',
            'camp_num' =>'',
            "nick_name_id" => 1,
            "action" => 'all',
            'type'=>'direct',
        ];

        $this->actingAs($user)
        ->post('/api/v3/support/remove',$data);
        $this->assertEquals(200, $this->response->status());
    }

    public function testUpateSupportWithValidaData()
    {
        $user = User::factory()->make();
        $data = [
            "topic_num" => '1',
            'camp_num' =>'',
            "nick_name_id" => 1,
            "order_update" => [
                [
                    'camp_num' => 2,
                    'order'=> 2
                ]
            ]
        ];
        $this->actingAs($user)
        ->post('/api/v3/support-order/update',$data);
        $this->assertEquals(200, $this->response->status());
    }
    
}
