<?php
namespace SimpleJWTLoginTest\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\AuthCodesSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class AuthCodesSettingsTest extends TestCase
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

    public function testAssignCodesFromPost()
    {
        $authCodesSettigs = (new AuthCodesSettings())
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
        $authCodesSettigs->initSettingsFromPost();
        $authCodesSettigs->validateSettings();
        $codes = $authCodesSettigs->getAuthCodes();

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
            $authCodesSettigs->getAuthCodeKey()
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
                    'require_login_auth'    => '1',
                    'allow_autologin'       => '1',
                    'require_register_auth' => '1',
                    'allow_register'        => '1',
                    'require_delete_auth'   => '1',
                    'allow_delete'          => '1',
                ]
            )
            ->withPost([]);
        $authCodesSettings->validateSettings();
    }
}