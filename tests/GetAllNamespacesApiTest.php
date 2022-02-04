<?php

class GetAllNamespacesApiTest extends TestCase
{

    public function testGetAllNamespacesApi()
    {
        print sprintf("Call the GetAllNamespaces Api");
        $response = $this->call('GET', '/api/v3/get_all_namespaces', []);
        $this->assertEquals(200, $response->status());       
    }
}
