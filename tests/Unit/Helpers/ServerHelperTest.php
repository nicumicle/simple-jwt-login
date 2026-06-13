<?php

namespace SimpleJwtLoginTests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;

class ServerHelperTest extends TestCase
{
    #[DataProvider('ipProvider')]
    /**
     * @param array $server
     * @param bool $useProxyHeaders
     * @param mixed $expectedResult
     */
    public function testGetClientIP($server, $useProxyHeaders, $expectedResult)
    {
        $serverHelper = $useProxyHeaders
            ? ServerHelper::withTrustedProxyHeaders($server)
            : new ServerHelper($server);
        $this->assertSame($expectedResult, $serverHelper->getClientIP());
    }

    /**
     * @return array[]
     */
    public static function ipProvider()
    {
        return [
            'empty server' => [
                'server' => [],
                'useProxyHeaders' => false,
                'expectedResult' => null,
            ],
            'client-ip header is ignored by default' => [
                'server' => [
                    'HTTP_CLIENT_IP' => '10.0.0.1',
                    'REMOTE_ADDR' => '127.0.0.1',
                ],
                'useProxyHeaders' => false,
                'expectedResult' => '127.0.0.1',
            ],
            'x-forwarded-for header is ignored by default' => [
                'server' => [
                    'HTTP_X_FORWARDED_FOR' => '10.0.0.1',
                    'REMOTE_ADDR' => '127.0.0.1',
                ],
                'useProxyHeaders' => false,
                'expectedResult' => '127.0.0.1',
            ],
            'spoofed headers without remote addr return null' => [
                'server' => [
                    'HTTP_CLIENT_IP' => '10.0.0.1',
                    'HTTP_X_FORWARDED_FOR' => '10.0.0.2',
                ],
                'useProxyHeaders' => false,
                'expectedResult' => null,
            ],
            'remote addr' => [
                'server' => [
                    'REMOTE_ADDR' => '127.0.0.1',
                ],
                'useProxyHeaders' => false,
                'expectedResult' => '127.0.0.1',
            ],
            'client-ip header used when proxy headers are trusted' => [
                'server' => [
                    'HTTP_CLIENT_IP' => '10.0.0.1',
                    'REMOTE_ADDR' => '127.0.0.1',
                ],
                'useProxyHeaders' => true,
                'expectedResult' => '10.0.0.1',
            ],
            'x-forwarded-for used when proxy headers are trusted' => [
                'server' => [
                    'HTTP_X_FORWARDED_FOR' => '10.0.0.1',
                    'REMOTE_ADDR' => '127.0.0.1',
                ],
                'useProxyHeaders' => true,
                'expectedResult' => '10.0.0.1',
            ],
            'x-forwarded-for takes the right-most hop' => [
                'server' => [
                    'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 10.0.0.2, 10.0.0.3',
                    'REMOTE_ADDR' => '127.0.0.1',
                ],
                'useProxyHeaders' => true,
                'expectedResult' => '10.0.0.3',
            ],
            'empty proxy headers fall back to remote addr' => [
                'server' => [
                    'HTTP_CLIENT_IP' => '',
                    'HTTP_X_FORWARDED_FOR' => '',
                    'REMOTE_ADDR' => '127.0.0.1',
                ],
                'useProxyHeaders' => true,
                'expectedResult' => '127.0.0.1',
            ],
        ];
    }

    #[DataProvider('isClientInListProvider')]
    /**
     * @param mixed $list
     * @param bool $result
     */
    public function testIsClientIpInList($list, $result)
    {
        $serberHelper = new ServerHelper(['REMOTE_ADDR' => '127.0.0.1']);
        $this->assertSame($result, $serberHelper->isClientIpInList($list));
    }

    /**
     * @return array[]
     */
    public static function isClientInListProvider()
    {
        return [
            [
                'list' => '',
                'result' => false,
            ],
            [
                'list' => '127.0.0.1',
                'result' => true,
            ],
            [
                'list' => '1, 127.0.0.1',
                'result' => true,
            ],
            [
                'list' => '127.0.0,127.0.0.1',
                'result' => true,
            ],
            [
                'list' => '127. 0 .0 1',
                'result' => false
            ],
            [
                'list' => ' 127.0.0.1  ',
                'result' => true,
            ],
            [
                'list' => '127.0.0.0, 127.0.0.2',
                'result' => false
            ]
        ];
    }

    #[DataProvider('getHeadersProvider')]
    /**
     * @param array $server
     * @param array $expectedResult
     */
    public function testGetHeaders($server, $expectedResult)
    {
        $serverHelper = new ServerHelper($server);
        $this->assertSame(
            $expectedResult,
            $serverHelper->getHeaders()
        );
    }

    /**
     * @return array
     */
    public static function getHeadersProvider()
    {
        return [
            [
                'server' => [],
                'expectedResult' => []
            ],
            [
                'server' => [
                    'HTTP_CUSTOM_HEADER' => 1
                ],
                'expectedResult' => [
                    'Custom-Header' => 1
                ],
            ],
            [
                'server' => [
                    'HTTP_Authorization' => 'Bearer 123',
                ],
                'expectedResult' => [
                    'Authorization' => 'Bearer 123'
                ]
            ]
        ];
    }

    #[DataProvider('providerWildIps')]
    public function testIsClientIpInListWildCard($ipList, $expectedResult)
    {
        $serverHelper = new ServerHelper(['REMOTE_ADDR' => '127.0.0.1']);
        $this->assertSame($expectedResult, $serverHelper->isClientIpInList($ipList));
    }

    /**
     * @return array[]
     */
    public static function providerWildIps()
    {
        return [
            [
                '127.0.0.1',
                true,
            ],
            [
                '127.0.0.2',
                false,
            ],
            [
                '127.0.0.*',
                true
            ],
            [
                '127.0.*.*',
                true
            ],
            [
                '127.*.*.*',
                true
            ],
            [
                '127.*.*.1',
                true
            ],
            [
                '*.*.*.*',
                true
            ],
            [
                '127.*.*.2',
                false
            ],
            [
                '127.*.1.*',
                false
            ],
            [
                '127.2.*.*',
                false
            ],
            [
                '*.*.*.2',
                false
            ],
            [
                '127.*.*.2, 127.2.*.*',
                false
            ],
            [
                // Regression: non-matching wildcard must not short-circuit the loop.
                // 127.*.*.2 does not match 127.0.0.1, but 127.0.0.* does.
                '127.*.*.2, 127.0.0.*',
                true
            ],
            [
                '127.0.0.1',
                true
            ]
        ];
    }

    public function testGetRequestMethod()
    {
        $serverHelper = new ServerHelper([
            'REQUEST_METHOD' => 'POST',
        ]);
        $this->assertSame('POST', $serverHelper->getRequestMethod());
    }
}
