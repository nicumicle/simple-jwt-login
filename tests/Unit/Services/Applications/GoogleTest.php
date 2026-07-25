<?php

namespace SimpleJwtLoginTests\Unit\Services\Applications;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Services\Applications\Google;

class GoogleTest extends TestCase
{
    const CLIENT_ID = '1234567890-abcdef.apps.googleusercontent.com';

    /**
     * @param array|null $tokenInfo
     * @param string $clientId
     * @param int $expectedErrorCode
     */
    #[DataProvider('invalidTokenInfoProvider')]
    public function testValidateTokenInfoClaimsRejectsInvalidToken($tokenInfo, $clientId, $expectedErrorCode)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedErrorCode);

        Google::validateTokenInfoClaims($tokenInfo, $clientId);
    }

    /**
     * @return array
     */
    public static function invalidTokenInfoProvider()
    {
        return [
            'token issued for another oauth client' => [
                'tokenInfo' => [
                    'iss' => 'https://accounts.google.com',
                    'aud' => '407408718192.apps.googleusercontent.com',
                    'email' => 'test@example.com',
                    'email_verified' => 'true',
                ],
                'clientId' => self::CLIENT_ID,
                'expectedErrorCode' => ErrorCodes::ERR_GOOGLE_ID_TOKEN_INVALID_AUDIENCE,
            ],
            'missing audience' => [
                'tokenInfo' => [
                    'iss' => 'https://accounts.google.com',
                    'email' => 'test@example.com',
                    'email_verified' => 'true',
                ],
                'clientId' => self::CLIENT_ID,
                'expectedErrorCode' => ErrorCodes::ERR_GOOGLE_ID_TOKEN_INVALID_AUDIENCE,
            ],
            'client id not configured' => [
                'tokenInfo' => [
                    'iss' => 'https://accounts.google.com',
                    'aud' => '',
                    'email' => 'test@example.com',
                    'email_verified' => 'true',
                ],
                'clientId' => '',
                'expectedErrorCode' => ErrorCodes::ERR_GOOGLE_ID_TOKEN_INVALID_AUDIENCE,
            ],
            'email not verified' => [
                'tokenInfo' => [
                    'iss' => 'https://accounts.google.com',
                    'aud' => self::CLIENT_ID,
                    'email' => 'test@example.com',
                    'email_verified' => 'false',
                ],
                'clientId' => self::CLIENT_ID,
                'expectedErrorCode' => ErrorCodes::ERR_GOOGLE_ID_TOKEN_EMAIL_NOT_VERIFIED,
            ],
            'email_verified missing' => [
                'tokenInfo' => [
                    'iss' => 'https://accounts.google.com',
                    'aud' => self::CLIENT_ID,
                    'email' => 'test@example.com',
                ],
                'clientId' => self::CLIENT_ID,
                'expectedErrorCode' => ErrorCodes::ERR_GOOGLE_ID_TOKEN_EMAIL_NOT_VERIFIED,
            ],
            'issuer is not google' => [
                'tokenInfo' => [
                    'iss' => 'https://evil.example.com',
                    'aud' => self::CLIENT_ID,
                    'email' => 'test@example.com',
                    'email_verified' => 'true',
                ],
                'clientId' => self::CLIENT_ID,
                'expectedErrorCode' => ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN,
            ],
            'missing email' => [
                'tokenInfo' => [
                    'iss' => 'https://accounts.google.com',
                    'aud' => self::CLIENT_ID,
                    'email_verified' => 'true',
                ],
                'clientId' => self::CLIENT_ID,
                'expectedErrorCode' => ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN,
            ],
            'empty response' => [
                'tokenInfo' => null,
                'clientId' => self::CLIENT_ID,
                'expectedErrorCode' => ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN,
            ],
        ];
    }

    /**
     * @param array $tokenInfo
     */
    #[DataProvider('validTokenInfoProvider')]
    public function testValidateTokenInfoClaimsAcceptsValidToken($tokenInfo)
    {
        $result = Google::validateTokenInfoClaims($tokenInfo, self::CLIENT_ID);

        $this->assertSame('test@example.com', $result['email']);
    }

    /**
     * @return array
     */
    public static function validTokenInfoProvider()
    {
        return [
            'issuer with scheme and string email_verified' => [
                'tokenInfo' => [
                    'iss' => 'https://accounts.google.com',
                    'aud' => self::CLIENT_ID,
                    'email' => 'test@example.com',
                    'email_verified' => 'true',
                ],
            ],
            'issuer without scheme and boolean email_verified' => [
                'tokenInfo' => [
                    'iss' => 'accounts.google.com',
                    'aud' => self::CLIENT_ID,
                    'email' => 'test@example.com',
                    'email_verified' => true,
                ],
            ],
        ];
    }
}
