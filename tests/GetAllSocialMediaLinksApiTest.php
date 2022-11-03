<?php

class GetAllSocialMediaLinksApiTest extends TestCase
{

    public function testGetAllSocialMediaLinksApi()
    {
        print sprintf("Call the GetAllSocialMediaLinks Api");
        $response = $this->call('GET', '/api/v3/get-social-media-links', []);
        $this->assertEquals(200, $response->status());       
    }
}
