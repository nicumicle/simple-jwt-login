<?php

namespace SimpleJwtLoginTests\Modules;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\CorsService;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;

class CorsServiceTest extends TestCase
{
    /**
     * @param array $settingsArray
     *
     * @return SimpleJWTLoginSettings
     */
    private function getSettingsMock($settingsArray)
    {
        $wordPressDataMock = $this->getMockBuilder(WordPressData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settingsArray));

        return new SimpleJWTLoginSettings($wordPressDataMock);
    }

    /**
     * @param array $settingsArray
     *
     * @return CorsService
     */
    private function initCorsServiceFromSettings($settingsArray)
    {
        return new CorsService($this->getSettingsMock($settingsArray));
    }

    /**
     * @dataProvider corsEnabledProvider
     *
     * @param array $settingsArray
     */
    public function testIsCorsEnabledReturnsTrue($settingsArray)
    {
        $corsService = $this->initCorsServiceFromSettings($settingsArray);
        $this->assertTrue($corsService->isCorsEnabled());
    }

    public function corsEnabledProvider()
    {
        return [
            [
                'settings' => [
                    'cors' => [
                        'enabled' => true,
                    ]
                ],
            ],
            [
                'settings' => [
                    'cors' => [
                        'enabled' => 1,
                    ]
                ],
            ],
            [
                'settings' => [
                    'cors' => [
                        'enabled' => '1',
                    ]
                ],
            ],
            [
                'settings' => [
                    'cors' => [
                        'enabled' => 'true',
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerCorsEnabledFalse
     *
     * @param array $settingsArray
     */
    public function testIsCorsEnabledReturnsFalse($settingsArray)
    {
        $corsService = $this->initCorsServiceFromSettings($settingsArray);
        $this->assertFalse($corsService->isCorsEnabled());
    }

    public function providerCorsEnabledFalse()
    {
        return [
            [
                'settings' => '',
            ],
            [
                'settings' => [],
            ],
            [
                'settings' => [
                    'cors' => [

                    ],
                ],
            ],
            [
                'settings' => [
                    'cors' => [
                        'enabled' => '',
                    ],
                ],
            ],
            [
                'settings' => [
                    'cors' => [
                        'enabled' => false,
                    ],
                ],
            ],
            [
                'settings' => [
                    'cors' => [
                        'enabled' => '0',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerTestIsAllowOriginEnabled
     *
     * @param array $settings
     * @param boolean $expected
     */
    public function testIsAllowOriginEnabled($settings, $expected)
    {
        $corsService = $this->initCorsServiceFromSettings($settings);
        $this->assertSame($expected, $corsService->isAllowOriginEnabled());
    }

    public function providerTestIsAllowOriginEnabled()
    {
        return [
            0 => [
                'settings' => [],
                'expected' => false,
            ],
            1 => [
                'settings' => [
                    'cors' => []
                ],
                'expected' => false,
            ],
            2 => [
                'settings' => [
                    'cors' => [
                        'allow_origin_enabled' => ''
                    ]
                ],
                'expected' => false,
            ],
            3 => [
                'settings' => [
                    'cors' => [
                        'allow_origin_enabled' => '0'
                    ]
                ],
                'expected' => false,
            ],
            4 => [
                'settings' => [
                    'cors' => [
                        'allow_origin_enabled' => false
                    ]
                ],
                'expected' => false,
            ],
            5 => [
                'settings' => [
                    'cors' => [
                        'allow_origin_enabled' => '1'
                    ]
                ],
                'expected' => true,
            ],
            6 => [
                'settings' => [
                    'cors' => [
                        'allow_origin_enabled' => 1
                    ]
                ],
                'expected' => true,
            ],
            7 => [
                'settings' => [
                    'cors' => [
                        'allow_origin_enabled' => true
                    ]
                ],
                'expected' => true,
            ],
            8 => [
                'settings' => [
                    'cors' => [
                        'allow_origin_enabled' => 'blaa',
                    ]
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider providerTestGetAllowOrigin
     *
     * @param array $settings
     * @param boolean $expected
     */
    public function testGetAllowOrigin($settings, $expected)
    {
        $corsService = $this->initCorsServiceFromSettings($settings);
        $this->assertSame($expected, $corsService->getAllowOrigin());
    }

    public function providerTestGetAllowOrigin()
    {
        return [
            0 => [
                'settings' => [],
                'expected' => CorsService::DEFAULT_HEADER_PARAMETER,
            ],
            1 => [
                'settings' => [
                    'allow_origin' => CorsService::DEFAULT_HEADER_PARAMETER,
                ],
                'expected' => CorsService::DEFAULT_HEADER_PARAMETER,
            ],
            2 => [
                'settings' => [
                    'cors' => [
                        'allow_origin' => CorsService::DEFAULT_HEADER_PARAMETER,
                    ]
                ],
                'expected' => CorsService::DEFAULT_HEADER_PARAMETER,
            ],
            3 => [
                'settings' => [
                    'cors' => [
                        'allow_origin' => '',
                    ]
                ],
                'expected' => ''
            ],
            4 => [
                'settings' => [
                    'cors' => [
                        'allow_origin' => '123',
                    ]
                ],
                'expected' => '123'
            ],
        ];
    }

    /**
     * @dataProvider providerAllowHeadersEnabled
     * @param array $settings
     * @param boolean $expected
     */
    public function testIsAllowHeadersEnabled($settings, $expected)
    {
        $corsService = $this->initCorsServiceFromSettings($settings);
        $this->assertSame($expected, $corsService->isAllowHeadersEnabled());
    }

    public function providerAllowHeadersEnabled()
    {
        return [
            0 => [
                'settings' => [],
                'expected' => false
            ],
            1 => [
                'settings' => [
                    'cors' => []
                ],
                'expected' => false
            ],
            2 => [
                'settings' => [
                    'cors' => [
                        'allow_headers_enabled' => '',
                    ]
                ],
                'expected' => false
            ],
            3 => [
                'settings' => [
                    'cors' => [
                        'allow_headers_enabled' => '0',
                    ]
                ],
                'expected' => false
            ],
            4 => [
                'settings' => [
                    'cors' => [
                        'allow_headers_enabled' => false,
                    ]
                ],
                'expected' => false
            ],
            5 => [
                'settings' => [
                    'cors' => [
                        'allow_headers_enabled' => 'false',
                    ]
                ],
                'expected' => false
            ],
            6 => [
                'settings' => [
                    'cors' => [
                        'allow_headers_enabled' => '1',
                    ]
                ],
                'expected' => true
            ],
            7 => [
                'settings' => [
                    'cors' => [
                        'allow_headers_enabled' => 1,
                    ]
                ],
                'expected' => true
            ],
            8 => [
                'settings' => [
                    'cors' => [
                        'allow_headers_enabled' => 'true',
                    ]
                ],
                'expected' => true
            ],
            9 => [
                'settings' => [
                    'cors' => [
                        'allow_headers_enabled' => true,
                    ]
                ],
                'expected' => true
            ],
            10 => [
                'settings' => [
                    'cors' => [
                        'allow_headers_enabled' => 'bla',
                    ]
                ],
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider providerGetAllowHeaders
     * @param array $settings
     * @param boolean $expected
     */
    public function testGetAllowHeaders($settings, $expected)
    {
        $corsService = $this->initCorsServiceFromSettings($settings);
        $this->assertSame($expected, $corsService->getAllowHeaders());
    }

    public function providerGetAllowHeaders()
    {
        return [
            0 => [
                'settings' => [],
                'expected' => CorsService::DEFAULT_HEADER_PARAMETER
            ],
            1 => [
                'settings' => [
                    'cors' => []
                ],
                'expected' => CorsService::DEFAULT_HEADER_PARAMETER
            ],
            2 => [
                'settings' => [
                    'cors' => [
                        'allow_headers' => ''
                    ]
                ],
                'expected' => '',
            ],
            3 => [
                'settings' => [
                    'cors' => [
                        'allow_headers' => '*'
                    ]
                ],
                'expected' => '*',
            ],
            4 => [
                'settings' => [
                    'cors' => [
                        'allow_headers' => '123'
                    ]
                ],
                'expected' => '123',
            ]
        ];
    }

    /**
     * @dataProvider providerIsAllowMethodsEnabled
     * @param array $settings
     * @param boolean $expected
     */
    public function testIsAllowMethodsEnabled($settings, $expected)
    {
        $corsService = $this->initCorsServiceFromSettings($settings);
        $this->assertSame($expected, $corsService->isAllowMethodsEnabled());
    }

    public function providerIsAllowMethodsEnabled()
    {
        return [
            0 => [
                'settings' => [],
                'expected' => false,
            ],
            1 => [
                'settings' => [
                    'cors' => [],
                ],
                'expected' => false,
            ],
            2 => [
                'settings' => [
                    'cors' => [
                        'allow_methods_enabled' => '',
                    ]
                ],
                'expected' => false,
            ],
            3 => [
                'settings' => [
                    'cors' => [
                        'allow_methods_enabled' => 'false',
                    ]
                ],
                'expected' => false,
            ],
            4 => [
                'settings' => [
                    'cors' => [
                        'allow_methods_enabled' => '0',
                    ]
                ],
                'expected' => false,
            ],
            5 => [
                'settings' => [
                    'cors' => [
                        'allow_methods_enabled' => false,
                    ]
                ],
                'expected' => false,
            ],
            6 => [
                'settings' => [
                    'cors' => [
                        'allow_methods_enabled' => 1,
                    ]
                ],
                'expected' => true,
            ],
            7 => [
                'settings' => [
                    'cors' => [
                        'allow_methods_enabled' => '1',
                    ]
                ],
                'expected' => true,
            ],
            8 => [
                'settings' => [
                    'cors' => [
                        'allow_methods_enabled' => true,
                    ]
                ],
                'expected' => true,
            ],
            9 => [
                'settings' => [
                    'cors' => [
                        'allow_methods_enabled' => 'true',
                    ]
                ],
                'expected' => true,
            ],
            10 => [
                'settings' => [
                    'cors' => [
                        'allow_methods_enabled' => 'bla',
                    ]
                ],
                'expected' => false,
            ],

        ];
    }

    /**
     * @dataProvider providerGetAllowMethods
     * @param array $settings
     * @param boolean $expected
     */
    public function testGetAllowMethods($settings, $expected)
    {
        $corsService = $this->initCorsServiceFromSettings($settings);
        $this->assertSame($expected, $corsService->getAllowMethods());
    }

    public function providerGetAllowMethods()
    {
        return [
            0 => [
                'settings' => [],
                'expected' => CorsService::DEFAULT_METHODS,
            ],
            1 => [
                'settings' => [
                    'cors' => []
                ],
                'expected' => CorsService::DEFAULT_METHODS,
            ],
            2 => [
                'settings' => [
                    'cors' => [
                        'allow_methods' => '',
                    ]
                ],
                'expected' => '',
            ],
            3 => [
                'settings' => [
                    'cors' => [
                        'allow_methods' => '1',
                    ]
                ],
                'expected' => '1',
            ],
            4 => [
                'settings' => [
                    'cors' => [
                        'allow_methods' => CorsService::DEFAULT_METHODS,
                    ]
                ],
                'expected' => CorsService::DEFAULT_METHODS,
            ],
        ];
    }
}
