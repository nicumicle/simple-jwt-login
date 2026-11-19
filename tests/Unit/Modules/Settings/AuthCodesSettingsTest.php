<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\AuthCodesSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class AuthCodesSettingsTest extends TestCase
{
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->createStub(WordPressDataInterface::class);
        $this->wordPressData->method('sanitizeTextField')
            ->willReturnCallback(
                function ($parameter) {
                    return $parameter;
                }
            );
    }

    public function testAssignCodesFromPost()
    {
        $this->wordPressData->method('roleExists')
            ->willReturn(true);
        $authCodesSettings = (new AuthCodesSettings())
            ->withSettings([])
            ->withPost(
                [
                    'auth_codes'    => [
                        'code'            => [
                            '1',
                            '', //one empty code that should be ignored
                        ],
                        'role'            => [
                            'subscriber'
                        ],
                        'expiration_date' => [
                            '2099-01-01 11:11:11'
                        ],
                    ],
                    'auth_code_key' => 'AUTH_CODE_KEY'
                ]
            )
            ->withWordPressData($this->wordPressData);
        $authCodesSettings->initSettingsFromPost();
        $authCodesSettings->validateSettings();
        $codes = $authCodesSettings->getAuthCodes();

        $this->assertSame(
            [
                [
                    'code'            => '1',
                    'role'            => 'subscriber',
                    'expiration_date' => '2099-01-01 11:11:11'
                ]
            ],
            $codes
        );
        $this->assertSame(
            'AUTH_CODE_KEY',
            $authCodesSettings->getAuthCodeKey()
        );
    }

    public function testValidation()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing Auth Codes. Please add at least one Auth Code.');
        $authCodesSettings = (new AuthCodesSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings(
                [
                    'login'       => ['auth_code' => '1', 'enabled' => '1'],
                    'register'    => ['auth_code' => '1', 'enabled' => '1'],
                    'delete_user' => ['auth_code' => '1', 'enabled' => '1'],
                ]
            )
            ->withPost([]);
        $authCodesSettings->validateSettings();
    }

    public function testInvalidUserRoles()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid role provided.');

        $this->wordPressData->method('roleExists')
            ->willReturn(false);

        $authCodesSettings = (new AuthCodesSettings())
            ->withSettings([])
            ->withPost(
                [
                    'auth_codes'    => [
                        'code'            => [
                            '1',
                            '', //one empty code that should be ignored
                        ],
                        'role'            => [
                            'subscriber'
                        ],
                        'expiration_date' => [
                            '2099-01-01 11:11:11'
                        ],
                    ],
                    'auth_code_key' => 'AUTH_CODE_KEY'
                ]
            )
            ->withWordPressData($this->wordPressData);
        $authCodesSettings->initSettingsFromPost();
        $authCodesSettings->validateSettings();
    }

    /**
     * @return array<string, array{mixed, string|null}>
     */
    public static function findValidAuthCodeProvider(): array
    {
        return [
            'matching code'                  => ['valid-code', 'editor'],
            'numeric request value'          => [123, ''],
            'wrong code'                     => ['nope', null],
            'expired code'                   => ['expired-code', null],
            'empty code never matches'       => ['', null],
            'array request value is ignored' => [['valid-code'], null],
            'missing request value'          => [null, null],
        ];
    }

    /**
     * One lookup for every auth code check, constant-time and expiry-aware.
     *
     * @param mixed $providedCode
     */
    #[DataProvider('findValidAuthCodeProvider')]
    public function testFindValidAuthCode($providedCode, ?string $expectedRole): void
    {
        $authCodesSettings = (new AuthCodesSettings())->withSettings([
            'auth_codes' => [
                'codes' => [
                    ['code' => '', 'role' => 'administrator', 'expiration_date' => ''],
                    ['code' => 'expired-code', 'role' => 'administrator', 'expiration_date' => '2000-01-01 00:00:00'],
                    ['code' => 'valid-code', 'role' => 'editor', 'expiration_date' => ''],
                    ['code' => 123, 'role' => '', 'expiration_date' => ''],
                ],
            ],
        ]);

        $authCode = $authCodesSettings->findValidAuthCode($providedCode);

        $this->assertSame($expectedRole, $authCode === null ? null : $authCode->getRole());
    }
}
