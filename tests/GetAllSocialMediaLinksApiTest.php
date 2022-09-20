<?php

class GetAllSocialMediaLinksApiTest extends TestCase
{

    public function testGetAllSocialMediaLinksApi()
    {
        print sprintf("Call the GetAllSocialMediaLinks Api");
        $response = $this->call('GET', '/api/v3/get_social_media_links', []);
        $this->assertEquals(200, $response->status());       
    }
}
