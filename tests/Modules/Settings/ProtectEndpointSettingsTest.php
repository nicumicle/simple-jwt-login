<?php

declare(strict_types=1);


namespace SimpleJwtLoginTests\Modules\Settings;

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
        $protectEndpointSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost(
                [
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
                ]
            )
            ->withWordPressData($this->wordPressData);
        $protectEndpointSettings->initSettingsFromPost();

        $this->assertSame(
            true,
            $protectEndpointSettings->isEnabled()
        );

        $this->assertSame(
            ProtectEndpointSettings::ALL_ENDPOINTS,
            $protectEndpointSettings->getAction()
        );

        $this->assertSame(
            [
                '123',
                ''
            ],
            $protectEndpointSettings->getProtectedEndpoints()
        );

        $this->assertSame(
            [
                'abc',
                ''
            ],
            $protectEndpointSettings->getWhitelistedDomains()
        );
    }

    public function testNoErrorIsThrownWhenDisabled()
    {
        $protectEndpointSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost(
                [
                    ProtectEndpointSettings::PROPERTY_GROUP => [
                        'enabled' => '0',
                        'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                        'protect' => [
                        ],
                        'whitelist' => [
                        ]
                    ]
                ]
            )->withWordPressData($this->wordPressData);
        $protectEndpointSettings->initSettingsFromPost();;
        $protectEndpointSettings->validateSettings();
        $this->assertFalse($protectEndpointSettings->isEnabled());
    }

    public function testExceptionIsThrownWhenNoEndpointIsAdded()
    {
        $protectEndpointSettings = (new ProtectEndpointSettings())
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
        $protectEndpointSettings->initSettingsFromPost();

        $protectEndpointSettings->validateSettings();
        $this->assertTrue($protectEndpointSettings->isEnabled());
    }
}