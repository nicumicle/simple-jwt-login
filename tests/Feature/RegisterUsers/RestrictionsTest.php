<?php

namespace SimpleJwtLoginTests\Feature\RegisterUsers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Feature tests for register endpoint restrictions:
 *   - feature toggle (allow_register = false)
 *   - email domain allowlist (register_domain)
 *   - client IP allowlist (register_ip)
 */
class RestrictionsTest extends FeatureTestCase
{
    /**
     * @return array<string,mixed>
     */
    private static function baseSettings(): array
    {
        return [
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'register_ip'             => '',
            'register_domain'         => '',
            'require_register_auth'   => false,
            'random_password'         => false,
            'random_password_length'  => 10,
            'register_force_login'    => false,
            'register_jwt'            => false,
            'allowed_user_meta'       => '',
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::baseSettings());
    }

    // ─── Feature toggle ───────────────────────────────────────────────────────

    #[TestDox('Register returns 403 when allow_register is false')]
    public function testRegisterDisabledReturns403(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), ['allow_register' => false]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
                'email'    => 'anyone@example.com',
                'password' => 'pass',
            ]);

            $this->assertSame(403, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    // ─── Domain restriction ───────────────────────────────────────────────────

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function domainRestrictionProvider(): array
    {
        return [
            'email from wrong domain' => [
                'email'       => 'user@wrong.com',
                'allowedDomain' => 'allowed.com',
                'expectPass'  => false,
            ],
            'email from allowed domain' => [
                'email'       => 'user@allowed.com',
                'allowedDomain' => 'allowed.com',
                'expectPass'  => true,
            ],
            'email from one of multiple allowed domains' => [
                'email'       => 'user@second.com',
                'allowedDomain' => 'first.com, second.com',
                'expectPass'  => true,
            ],
            'email from neither of multiple allowed domains' => [
                'email'       => 'user@third.com',
                'allowedDomain' => 'first.com, second.com',
                'expectPass'  => false,
            ],
        ];
    }

    /**
     * @param string $email
     * @param string $allowedDomain
     * @param bool   $expectPass
     */
    #[DataProvider('domainRestrictionProvider')]
    #[TestDox('Domain restriction correctly allows or rejects registration')]
    public function testDomainRestriction(string $email, string $allowedDomain, bool $expectPass): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'register_domain' => $allowedDomain,
        ]));

        try {
            $uniqueEmail = str_replace('@', '_' . uniqid() . '@', $email);
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
                'email'    => $uniqueEmail,
                'password' => 'SecurePass1!',
            ]);

            if ($expectPass) {
                $this->assertSame(200, $response->getStatusCode(), 'Expected registration to succeed');
                return;
            }

            $this->assertSame(422, $response->getStatusCode(), 'Expected domain rejection');
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(
                ErrorCodes::ERR_REGISTER_DOMAIN_FOR_USER,
                $body['data']['error_code']
            );
            $this->assertSame(
                'This website does not allows users from this domain.',
                $body['data']['message']
            );
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Domain restriction returns 422 with correct error message')]
    public function testDomainRejectionErrorMessageIsCorrect(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'register_domain' => 'corp.example.com',
        ]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
                'email'    => 'attacker@evil.com',
                'password' => 'pass',
            ]);

            $this->assertSame(422, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertSame(
                self::generateErrorJson(
                    'This website does not allows users from this domain.',
                    ErrorCodes::ERR_REGISTER_DOMAIN_FOR_USER
                ),
                $body
            );
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    // ─── IP restriction ───────────────────────────────────────────────────────

    #[TestDox('Register from a blocked IP returns 403')]
    public function testBlockedIpReturns403(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'register_ip' => '10.0.0.1',
        ]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
                'email'    => 'blocked_' . uniqid() . '@example.com',
                'password' => 'pass',
            ]);

            $this->assertSame(403, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_REGISTER_IP_IS_NOT_ALLOWED, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Register succeeds when IP restriction is empty (open access)')]
    public function testEmptyIpRestrictionAllowsAllIps(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), ['register_ip' => '']));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
                'email'    => 'open_' . uniqid() . '@example.com',
                'password' => 'pass',
            ]);

            $this->assertSame(200, $response->getStatusCode());
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    // ─── Auth code required on register ──────────────────────────────────────

    #[TestDox('Register requires an auth code when require_register_auth is true')]
    public function testRegisterRequiresAuthCodeWhenConfigured(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'require_register_auth' => true,
            'auth_codes'            => [
                ['code' => 'reg-code-xyz', 'role' => 'subscriber', 'expiration_date' => ''],
            ],
        ]));

        try {
            // Without auth code → rejected
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
                'email'    => 'nocode_' . uniqid() . '@example.com',
                'password' => 'pass',
            ]);

            $this->assertNotSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_AUTH_CODE_REQUIRED, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Register succeeds with a valid auth code')]
    public function testRegisterSucceedsWithValidAuthCode(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'require_register_auth' => true,
            'auth_codes'            => [
                ['code' => 'reg-code-xyz', 'role' => 'subscriber', 'expiration_date' => ''],
            ],
        ]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
                'email'    => 'withcode_' . uniqid() . '@example.com',
                'password' => 'pass',
                'AUTH_KEY' => 'reg-code-xyz',
            ]);

            $this->assertSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertTrue($body['success']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Register with an invalid auth code returns 401')]
    public function testRegisterWithInvalidAuthCodeReturns401(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'require_register_auth' => true,
            'auth_codes'            => [
                ['code' => 'reg-code-xyz', 'role' => 'subscriber', 'expiration_date' => ''],
            ],
        ]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
                'email'    => 'badcode_' . uniqid() . '@example.com',
                'password' => 'pass',
                'AUTH_KEY' => 'wrong-code',
            ]);

            $this->assertSame(401, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }
}
