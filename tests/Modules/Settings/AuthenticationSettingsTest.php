<?php


namespace SimpleJWTLoginTest\Settings;


use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class AuthenticationSettingsTest extends TestCase
{
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

    public function testAssignProperties(){
        $authenticationSettings = (new AuthenticationSettings())
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
                    'auth_ip' => '127.0.0.1'
                ]
            );
        $authenticationSettings->initSettingsFromPost();
        $authenticationSettings->validateSettings();

        $this->assertSame(
            true,
            $authenticationSettings->isAuthenticationEnabled()
        );
        $this->assertSame(
            true,
            $authenticationSettings->isPayloadDataEnabled('exp')
        );
        $this->assertSame(120, $authenticationSettings->getAuthJwtTtl());
        $this->assertSame(
            '127.0.0.1',
            $authenticationSettings->getAllowedIps()
        );
        $this->assertSame(
            130,
            $authenticationSettings->getAuthJwtRefreshTtl()
        );
        $this->assertIsArray($authenticationSettings->getJwtPayloadParameters());
    }

    public function testValidationSucceededWithMissingPayload()
    {
        $this->expectExceptionMessage('Authentication payload data can not be empty.');
        $this->expectException(Exception::class);
        $authenticationSettings = (new AuthenticationSettings())
            ->withSettings([])
            ->withPost(
                [
                    'allow_authentication' => 1
                ]
            )
            ->withWordPressData($this->wordPressData);
        $authenticationSettings->validateSettings();
    }

    public function testValidationWithTTLSmalledThanZero(){
        $this->expectExceptionMessage('Authentication JWT time to live should be greater than zero.');
        $this->expectException(Exception::class);
        $authenticationSettings = (new AuthenticationSettings())
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
        $authenticationSettings->validateSettings();
    }

    public function testValidationWithRefreshTTLSmalledThanZero(){
        $this->expectExceptionMessage('Authentication JWT Refresh time to live should be greater than zero.');
        $this->expectException(Exception::class);
        $authenticationSettings = (new AuthenticationSettings())
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
        $authenticationSettings->validateSettings();
    }

}