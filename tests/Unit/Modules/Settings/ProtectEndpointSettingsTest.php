<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class ProtectEndpointSettingsTest extends TestCase
{
    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
        $this->wordPressData->method('sanitizeTextField')
            ->willReturnCallback(
                function ($parameter) {
                    return $parameter;
                }
            );
    }

    public function testAssignCodesFromPost()
    {
        $protectSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled' => '1',
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect' => [
                        '123',
                        '',
                        '123'
                    ],
                    'whitelist' => [
                        'abc',
                        '',
                        'abc'
                    ]
                ]
            ])
            ->withWordPressData($this->wordPressData);
        $protectSettings->initSettingsFromPost();

        $this->assertSame(
            true,
            $protectSettings->isEnabled()
        );

        $this->assertSame(
            ProtectEndpointSettings::ALL_ENDPOINTS,
            $protectSettings->getAction()
        );

        $this->assertSame(
            [
                [
                    'url' => '123',
                    'method' => 'ALL',
                ],
                [
                    'url' => '123',
                    'method' => 'ALL',
                ],
            ],
            $protectSettings->getProtectedEndpoints()
        );

        $this->assertSame(
            [
                [
                    'url' => 'abc',
                    'method' => 'ALL',
                ],
                [
                    'url' => 'abc',
                    'method' => 'ALL',
                ],
            ],
            $protectSettings->getWhitelistedDomains()
        );
    }

    public function testAssignCodesFromPostWithHTTPMethods()
    {
        $protectSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled' => '1',
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect' => [
                        '/protect-first',
                        '/protect-second',
                        '/protect-third'
                    ],
                    'protect_method' => [
                        'GET',
                        'ALL',
                        'PUT',
                    ],
                    'whitelist' => [
                        '/whitelist-first',
                        '/whitelist-second',
                        '/whitelist-third'
                    ],
                    'whitelist_method' => [
                        'GET',
                        'ALL',
                        'PUT'
                    ]
                ]
            ])
            ->withWordPressData($this->wordPressData);
        $protectSettings->initSettingsFromPost();

        $this->assertSame(
            true,
            $protectSettings->isEnabled()
        );

        $this->assertSame(
            ProtectEndpointSettings::ALL_ENDPOINTS,
            $protectSettings->getAction()
        );

        $this->assertSame(
            [
                [
                    'url' => '/protect-first',
                    'method' => 'GET',
                ],
                [
                    'url' => '/protect-second',
                    'method' => 'ALL',
                ],
                [
                    'url' => '/protect-third',
                    'method' => 'PUT',
                ],

            ],
            $protectSettings->getProtectedEndpoints()
        );

        $this->assertSame(
            [
                [
                    'url' => '/whitelist-first',
                    'method' => 'GET',
                ],
                [
                    'url' => '/whitelist-second',
                    'method' => 'ALL',
                ],
                [
                    'url' => '/whitelist-third',
                    'method' => 'PUT',
                ],

            ],
            $protectSettings->getWhitelistedDomains()
        );
    }

    public function testNoErrorIsThrownWhenDisabled()
    {
        $protectSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled' => '0',
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect' => [
                    ],
                    'whitelist' => [
                    ]
                ]
            ])
            ->withWordPressData($this->wordPressData);
        $protectSettings->initSettingsFromPost();
        $protectSettings->validateSettings();
        $this->assertFalse($protectSettings->isEnabled());
    }

    public function testExceptionIsThrownWhenNoEndpointIsAdded()
    {
        $protectSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost(
                [
                    ProtectEndpointSettings::PROPERTY_GROUP => [
                        'enabled' => '1',
                        'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                        'protect' => [
                            '',
                            '0',
                            'null',
                        ]
                    ]
                ]
            )
            ->withWordPressData($this->wordPressData);
        $protectSettings->initSettingsFromPost();

        $protectSettings->validateSettings();
        $this->assertTrue($protectSettings->isEnabled());
    }

    public function testInitProperties()
    {
        $post = [
            ProtectEndpointSettings::PROPERTY_GROUP => [
                'enabled' => 1,
                'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect' => [
                    'test',
                    '',
                    'test'
                ],
                'whitelist' => [
                    '123',
                    '',
                    '123'
                ]
            ]
        ];
        $settings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost($post);
        $settings->initSettingsFromPost();
        $this->assertTrue($settings->isEnabled());
        $this->assertSame(
            ProtectEndpointSettings::ALL_ENDPOINTS,
            $settings->getAction()
        );
        $this->assertSame(
            [
                [
                    'url' => 'test',
                    'method' => 'ALL',
                ],
                [
                    'url' => 'test',
                    'method' => 'ALL',
                ],
            ],
            $settings->getProtectedEndpoints()
        );
        $this->assertSame(
            [
                [
                    'url' => '123',
                    'method' => 'ALL',
                ],
                [
                    'url' => '123',
                    'method' => 'ALL',
                ],
            ],
            $settings->getWhitelistedDomains()
        );
    }

    public function testGetDefaultValues()
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([]);

        $this->assertSame(
            false,
            $settings->isEnabled()
        );
        $this->assertSame(
            0,
            $settings->getAction()
        );
        $this->assertSame(
            [],
            $settings->getWhitelistedDomains()
        );
        $this->assertSame(
            [],
            $settings->getProtectedEndpoints()
        );
    }

    public function testValidateWhenNotEnabled()
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled' => false
                ]
            ]);
        $this->assertTrue($settings->validateSettings());
    }

    #[DataProvider('endpointsProvider')]
    /**
     * @param mixed $endpointLists
     * @throws Exception
     */
    public function testNoEndpointProvided($endpointLists)
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => $endpointLists
                ]
            ]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You need to add at least one endpoint.');
        $settings->validateSettings();
    }

    public static function endpointsProvider()
    {
        return [
            'empty_array' => [
                'endpointLists' => ['']
            ],
            'array_with_empty_values' => [
                'endpointLists' => [
                    '',
                    '',
                    '',
                ]
            ],
            'array_with_space' => [
                'endpointLists' => [
                    '    ',
                    '    ',
                ]
            ],
        ];
    }
}
