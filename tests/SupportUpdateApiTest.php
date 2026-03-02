<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
        print sprintf("\n Unauthorized User can not  request support re-order api %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/support-order/update', []);
        $_res->assertStatus(401);
    }

    /**
     * [unautorized user is restricted to perform this action]
     */
    public function testUnauthorizedUserCannotRemoveSupport()
    {
        print sprintf("\n Unauthorized User can not  request remove support  api %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/support/update', []);
        $_res->assertStatus(401);
    }

    /**
     *  [Remove support with invalid data]
     */
    public function testRemoveSupportWithInvalidData()
    {
        print sprintf("\n Remove or update direct  Support with invalid data %d %s", 400,PHP_EOL);
        $user = User::factory()->make();
        $data = [];
        $this->actingAs($user)
        ->post('/api/v3/support/update',$data);
        $_res->assertStatus(500);
    }


    public function testRemoveSupportWithValidaData()
    {
        print sprintf("\n Remove or update direct support with valid data %d %s", 200 ,PHP_EOL);

        $user = User::factory()->make();
        $data = [
            "topic_num" => '1',
            'camp_num' =>[],
            "nick_name_id" => 1,
            "action" => 'all',
            'type'=>'direct',
        ];

        $this->actingAs($user)
        ->post('/api/v3/support/update',$data);
        $_res->assertStatus(200);
    }

    public function testUpateSupportWithValidaData()
    {
        print sprintf("\n Support re-order api %d %s", 200,PHP_EOL);

        $user = User::factory()->make([
            'id' => '362',
        ]);

        $data = [
            "topic_num" => '173',
            'camp_num' =>'',
            "nick_name_id" => 347,
            "order_update" => [
                [
                    'camp_num' => 3,
                    'order'=> 1
                ]
            ]
        ];
        $this->actingAs($user)
        ->post('/api/v3/support-order/update',$data);
        $_res->assertStatus(200);
    }

    /**
     *  [unauthorized user is restricted to perform this action]
     *
     * @return void
     */
    public function testUnauthorizedUserCannotRemoveDelegateSupport()
    {
        print sprintf("\n Unauthorized User can not  request remove delegate support API. %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/support/remove-delegate', []);
        $_res->assertStatus(401);
    }

    public function testRemoveDelegataeSupporttWithValidaData()
    {
        print sprintf("\n Remove delegate support with valida data %d %s", 200,PHP_EOL);
        $user = User::factory()->make([
            'id' => '362',
        ]);
        $data = [
            "topic_num"=>"416",
            "nick_name_id" => "347",
            "delegated_nick_name_id" => "1"
        ];

        $_res = $this->actingAs($user)->post('/api/v3/support/remove-delegate',$data);
        $_res->assertStatus(200);
    }
    
}
