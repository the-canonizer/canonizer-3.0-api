<?php

class GetWhatNewContentApiTest extends TestCase
{

    public function testGetWhatNewContentApi()
    {
        print sprintf("Call the GetWhatNewContent Api");
        $response = $this->call('GET', '/api/v3/get-whats-new-content', []);
        $this->assertEquals(200, $response->status());       
    }
}
