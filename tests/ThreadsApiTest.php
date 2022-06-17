<?php

use App\Models\Camp;
use App\Models\Thread;
use Laravel\Lumen\Testing\WithoutMiddleware;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatementComparisonApiTest extends TestCase
{

    use WithoutMiddleware;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testStatementComparisonValidateFiled()
    {

        $rules = [
            'ids' => 'required',
        ];

        $data = ['ids'=>[2,3]];
            
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testStatementComparisonWithInvalidData()
    {
        print sprintf(" \n Invalid Statement Comparison details submitted %d %s", 400, PHP_EOL);

        $Thread = Thread::factory()->make();
        $parameter = [
            "id" => "",
        ];

        $this->actingAs($Thread)->post('/api/v3/get-statement-comparison', $parameter);
        $this->assertEquals(400, $this->response->status());
    }

    public function testThreadStoreWithValidData()
    {
        print sprintf(" \n Valid Statement Comparison details submitted %d %s", 200, PHP_EOL);

        $parameters = [
            "ids" => [2,3],
          ];
        $this->call('POST', '/api/v3/get-statement-comparison', $parameters);
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data' => []
        ]);
    }

}
