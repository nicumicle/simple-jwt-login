<?php

namespace SimpleJwtLoginTests\Libraries;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Libraries\ParseRequest;

class ParseRequestTest extends TestCase
{
    /**
     * @dataProvider contentTypeProvider
     *
     * @param mixed $server
     * @param mixed $expectedResult
     */
    public function testRequest($server, $expectedResult)
    {
        $requestParameters = ParseRequest::process($server);
        $this->assertSame($expectedResult, $requestParameters['variables']);
    }

    /**
     * @return array
     */
    public static function contentTypeProvider()
    {
        return [
            [
                'server' => [],
                'result' => []
            ],
            [
                'server' => [
                    'CONTENT_TYPE' => 'application/json'
                ],
                'result' => [],
            ],
            [
                'server' => [
                    'CONTENT_TYPE' => 'multipart/form-data; boundary=something test'
                ],
                'result' => [],
            ],
            [
                'server' => [
                    'CONTENT_TYPE' => 'text/html; charset=UTF-8'
                ],
                'result' => [],
            ],
            [
                'server' => [
                    'CONTENT_TYPE' => 'multipart/form-data'
                ],
                'result' => [],
            ]
        ];
    }
}
