<?php

namespace SimpleJwtLoginTests\Modules\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class AuthenticationSettingsTest extends TestCase
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

    public function testAssignProperties()
    {
        $authSettings = (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost(
                [
                    'allow_authentication' => '1',
                    'jwt_payload' => [
                        'iat',
                        'exp',
                        'id',
                    ],
                    'jwt_auth_ttl' => 120,
                    'jwt_auth_refresh_ttl' => 130,
                    'auth_ip' => '127.0.0.1',
                    'auth_requires_auth_code' => 1,
                ]
            );
        $authSettings->initSettingsFromPost();
        $authSettings->validateSettings();

        $this->assertSame(
            true,
            $authSettings->isAuthenticationEnabled()
        );
        $this->assertSame(
            true,
            $authSettings->isPayloadDataEnabled('exp')
        );
        $this->assertSame(120, $authSettings->getAuthJwtTtl());
        $this->assertSame(
            '127.0.0.1',
            $authSettings->getAllowedIps()
        );
        $this->assertSame(
            130,
            $authSettings->getAuthJwtRefreshTtl()
        );
        $this->assertIsArray($authSettings->getJwtPayloadParameters());
        $this->assertSame(
            true,
            $authSettings->isAuthKeyRequired()
        );
    }

    public function testValidationSucceededWithMissingPayload()
    {
        $this->expectExceptionMessage('Authentication payload data can not be empty.');
        $this->expectException(Exception::class);
        $authSettings = (new AuthenticationSettings())
            ->withSettings([])
            ->withPost(
                [
                    'allow_authentication' => 1
                ]
            )
            ->withWordPressData($this->wordPressData);
        $authSettings->validateSettings();
    }

    public function testValidationWithTTLSmalledThanZero()
    {
        $this->expectExceptionMessage('Authentication JWT time to live should be greater than zero.');
        $this->expectException(Exception::class);
        $authSettings = (new AuthenticationSettings())
            ->withSettings([])
            ->withPost(
                [
                    'allow_authentication' => 1,
                    'jwt_auth_ttl' => -1,
                    'jwt_payload' => [
                        'exp',
                        'id'
                    ]
                ]
            )
            ->withWordPressData($this->wordPressData);
        $authSettings->validateSettings();
    }

    public function testValidationWithRefreshTTLSmalledThanZero()
    {
        $this->expectExceptionMessage('Authentication JWT Refresh time to live should be greater than zero.');
        $this->expectException(Exception::class);
        $authSettings = (new AuthenticationSettings())
            ->withSettings([])
            ->withPost(
                [
                    'allow_authentication' => 1,
                    'jwt_auth_ttl' => 120,
                    'jwt_auth_refresh_ttl' => -1,
                    'jwt_payload' => [
                        'exp',
                        'id'
                    ]
                ]
            )
            ->withWordPressData($this->wordPressData);
        $authSettings->validateSettings();
    }
}
