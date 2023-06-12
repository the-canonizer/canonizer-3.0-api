<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseTransactions;

class EmbeddedCodeTrackingTest extends TestCase
{
    private $user;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
    }

    public function testEmbeddedCodeTrackingValidData()
    {
        $request = [
            'url' => 'http://example.com',
            'ip_address' => '192.168.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
        ];

        $this->actingAs($this->user)->post('/api/v3/embedded-code-tracking', $request);
        $this->assertEquals(200,  $this->response->status());
    }

    public function testEmbeddedCodeTrackingInvalidURL()
    {
        $request = [
            'url' => 'example.com',
            'ip_address' => '192.168.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
        ];

        $this->actingAs($this->user)->post('/api/v3/embedded-code-tracking', $request);
        $this->assertEquals(400,  $this->response->status());
    }
    
    public function testEmbeddedCodeTrackingInvalidIP()
    {
        $request = [
            'url' => 'http://example.com',
            'ip_address' => '192.oiuyt.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
        ];

        $this->actingAs($this->user)->post('/api/v3/embedded-code-tracking', $request);
        $this->assertEquals(400,  $this->response->status());
    }

    public function testEmbeddedCodeTrackingSameURL()
    {
        $request = [
            'url' => 'http://xyz.com',
            'ip_address' => '192.168.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
        ];

        $this->actingAs($this->user)->post('/api/v3/embedded-code-tracking', $request);

        $request = [
            'url' => 'http://xyz.com',
            'ip_address' => '192.168.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
        ];

        $this->actingAs($this->user)->post('/api/v3/embedded-code-tracking', $request);
        $this->assertEquals(400,  $this->response->status());
    
    }
    public function testEmbeddedCodeTrackingUserAgentMustBeString()
    {
        $request = [
            'url' => 'http://xyz.com',
            'ip_address' => '192.168.0.1',
            'user_agent' => 12344,
        ];
        $this->actingAs($this->user)->post('/api/v3/embedded-code-tracking', $request);
        $this->assertEquals(400,  $this->response->status());
    }

}
