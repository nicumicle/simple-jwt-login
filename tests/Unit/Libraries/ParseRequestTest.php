<?php

namespace SimpleJwtLoginTests\Unit\Libraries;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Libraries\ParseRequest;

class ParseRequestTest extends TestCase
{
    #[DataProvider('contentTypeProvider')]
    /**
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
                'expectedResult' => []
            ],
            [
                'server' => [
                    'CONTENT_TYPE' => 'application/json'
                ],
                'expectedResult' => [],
            ],
            [
                'server' => [
                    'CONTENT_TYPE' => 'multipart/form-data; boundary=something test'
                ],
                'expectedResult' => [],
            ],
            [
                'server' => [
                    'CONTENT_TYPE' => 'text/html; charset=UTF-8'
                ],
                'expectedResult' => [],
            ],
            [
                'server' => [
                    'CONTENT_TYPE' => 'multipart/form-data'
                ],
                'expectedResult' => [],
            ]
        ];
    }
}
