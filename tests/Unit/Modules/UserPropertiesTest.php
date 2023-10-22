<?php

namespace SimpleJwtLoginTests\Unit\Modules;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\UserProperties;

class UserPropertiesTest extends TestCase
{
    /**
     * @dataProvider providerBuildMethod
     * @param mixed $expected
     * @param mixed $userProperties
     * @param mixed $extraParameters
     */
    public function testBuildMethod($expected, $userProperties, $extraParameters)
    {
        $result = (new UserProperties())->build(
            $userProperties,
            $extraParameters
        );
        $this->assertSame($expected, $result);
    }

    public static function providerBuildMethod()
    {
        return [
            0 => [
                'expected' => [],
                'userProperties' => [],
                'extraParameters' => [],
            ],
            1 => [
                'expected' => [
                    'user_pass' => 1,
                    'user_login' => 1,
                    'user_email' => 1,
                ],
                'userProperties' => [
                    'user_pass' => 1,
                    'user_login' => 1,
                    'user_email' => 1,
                ],
                'extraParameters' => [
                    'user_pass' => 2,
                ],
            ],
            2 => [
                'expected' => [
                    'user_pass' => 1,
                    'user_login' => 2,
                    'user_email' => 1,
                ],
                'userProperties' => [
                    'user_pass' => 1,
                    'user_login' => 1,
                    'user_email' => 1,
                ],
                'extraParameters' => [
                    'user_pass' => 2,
                    'user_login' => 2,
                    'user_email' => 3,
                ],
            ],
            3 => [
                'expected' => [
                    'user_pass' => 1,
                    'user_login' => 1,
                    'user_email' => 1,
                    'user_nicename' => 1
                ],
                'userProperties' => [
                    'user_pass' => 1,
                    'user_login' => 1,
                    'user_email' => 1,
                ],
                'extraParameters' => [
                    'user_nicename' => 1
                ],
            ],
            4 => [
                'expected' => [
                    'user_pass' => 1,
                    'user_login' => 1,
                    'user_email' => 1,
                    'user_url' => 1,
                ],
                'userProperties' => [
                    'user_pass' => 1,
                    'user_login' => 1,
                    'user_email' => 1,
                ],
                'extraParameters' => [
                    'user_url' => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerTestGetExtraParametersFromRequest
     * @param array $expected
     * @param array $request
     */
    public function testGetExtraParametersFromRequest($expected, $request)
    {
        $result = UserProperties::getExtraParametersFromRequest($request);
        $this->assertSame($expected, $result);
    }

    public static function providerTestGetExtraParametersFromRequest()
    {
        return [
            0 => [
                'expected' => [],
                'request' => [],
            ],
            1 => [
                'expected' => [],
                'request' => [
                    '123' => 1,
                    'password' => 1,
                ],
            ],
            2 => [
                'expected' => [
                    'user_nicename' => 1,
                    'nickname' => 1,
                ],
                'request' => [
                    'user_nicename' => 1,
                    'nickname' => 1,
                    'password' => 1,
                    'password123' => 1,
                ],
            ]
        ];
    }
}
