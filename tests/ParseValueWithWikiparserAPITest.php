<?php

class ParseValueWithWikiparserAPITest extends TestCase
{
    public function testParseValueWithWikiparserAPIWithEmptyFormData()
    {
        print sprintf("Test with empty form data");
        $response = $this->call('POST', '/api/v3/parse-camp-statement', []);
        $this->assertEquals(200, $response->status());
    }
    
    /**
     * Check Api with empty values
     * validation
     */
    public function testParseValueWithWikiparserAPIWithEmptyValues()
    {
        $emptyData = [
            'value' => '',
        ];
        print sprintf("Test with empty values");
        $response = $this->call('POST', '/api/v3/parse-camp-statement', $emptyData);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Check Api with integer values
     * validation
     */
    public function testParseValueWithWikiparserAPIWithIntegerValues()
    {
        $data = [
            'value' => 213123,
        ];
        print sprintf("Test with integer values");
        $response = $this->call('POST', '/api/v3/parse-camp-statement', $data);
        $this->assertEquals(400, $response->status());
    }
    

    /**
     * Check Api response code with valid data
     */
    public function testParseValueWithWikiparserAPIStatus()
    {
        $data = [
            'value' => 'string to be parsed',
        ];
        print sprintf("\n Parse value ", 200, PHP_EOL);
        $response = $this->call(
            'post',
            '/api/v3/parse-camp-statement',
            $data
        );
        $this->assertEquals(200, $response->status());
    }
    
    /**
     * Check Api response structure
     */
    public function testParseValueWithWikiparserAPIResponse()
    {
        $data = [
            'value' => '',
        ];
        print sprintf("\n  Parse value API Response ", 200, PHP_EOL);
        $this->call(
            'post',
            '/api/v3/parse-camp-statement',
            $data
        );
        $this->seeJsonStructure([
            'status_code',
            'message',
            'error',
            'data'
        ]);
    }
}
