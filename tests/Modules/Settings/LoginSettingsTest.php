<?php
namespace SimpleJWTLoginTests\Modules\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class LoginSettingsTest extends TestCase
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

    public function testGetProperties()
    {
        $post = [
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter' => 'id',
            'allow_autologin' => '1',
            'redirect' => LoginSettings::REDIRECT_CUSTOM,
            'redirect_url' => 'http://localhost',
            'require_login_auth' => '0',
            'include_login_request_parameters' => '1',
            'allow_usage_redirect_parameter' => '1',
            'login_ip' => '127.0.0.1',
        ];
        $loginSettings = (new LoginSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost($post);
        $loginSettings->initSettingsFromPost();
        $loginSettings->validateSettings();

        $this->assertSame(true, $loginSettings->isAutologinEnabled());
        $this->assertSame(
            LoginSettings::JWT_LOGIN_BY_EMAIL,
            $loginSettings->getJWTLoginBy()
        );
        $this->assertSame(
            'id',
            $loginSettings->getJwtLoginByParameter()
        );
        $this->assertSame(
            LoginSettings::REDIRECT_CUSTOM,
            $loginSettings->getRedirect()
        );
        $this->assertSame(
            true,
            $loginSettings->getShouldIncludeRequestParameters()
        );

        $this->assertSame(
            'http://localhost',
            $loginSettings->getCustomRedirectURL()
        );
        $this->assertSame(
            false,
            $loginSettings->isAuthKeyRequiredOnLogin()
        );
        $this->assertSame(
            true,
            $loginSettings->isRedirectParameterAllowed()
        );
        $this->assertSame(
            '127.0.0.1',
            $loginSettings->getAllowedLoginIps()
        );
    }

    public function testJwtLoginByForOlderVersions()
    {
        $settings = [
            'jwt_email_parameter' => 'email'
        ];
        $loginSettings = (new LoginSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings($settings)
            ->withPost([]);
        $this->assertSame(
            'email',
            $loginSettings->getJwtLoginByParameter()
        );
    }

    public function testValidationEmptyLoginBy()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('JWT Parameter key from LoginSettings Config is missing.');
        $loginSettings = (new LoginSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost(
                [
                    'allow_autologin'        => 1,
                    'jwt_login_by_parameter' => ''
                ]
            );
        $loginSettings->initSettingsFromPost();
        $loginSettings->validateSettings();
    }

    public function testValidationInvalidRedirectURL()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid custom URL provided.');
        $loginSettings = (new LoginSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost(
                [
                    'allow_autologin'        => 1,
                    'jwt_login_by_parameter' => 'id',
                    'redirect' => LoginSettings::REDIRECT_CUSTOM,
                    'redirect_url' => 'http:/',
                ]
            );
        $loginSettings->initSettingsFromPost();
        $loginSettings->validateSettings();
    }
}
