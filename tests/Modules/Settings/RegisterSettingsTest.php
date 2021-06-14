<?php
namespace SimpleJWTLoginTests\Modules\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\RegisterSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class RegisterSettingsTest extends TestCase
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
        $post = [
            'allow_register' => '1',
            'new_user_profile' => 'subscriber',
            'register_ip' => '127.0.0.1',
            'register_domain' => 'test.com',
            'require_register_auth' => '0',
            'random_password' => '1',
            'register_force_login' => '1',
            'allowed_user_meta' => 'test',
        ];
        $registerSettings = (new RegisterSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $registerSettings->initSettingsFromPost();
        $registerSettings->validateSettings();

        $this->assertSame(
            true,
            $registerSettings->isRegisterAllowed()
        );
        $this->assertSame(
           'subscriber',
           $registerSettings->getNewUSerProfile()
        );
        $this->assertSame(
            '127.0.0.1',
            $registerSettings->getAllowedRegisterIps()
        );
        $this->assertSame(
            'test.com',
            $registerSettings->getAllowedRegisterDomain()
        );
        $this->assertSame(
            false,
            $registerSettings->isAuthKeyRequiredOnRegister()
        );

        $this->assertSame(
            true,
            $registerSettings->isRandomPasswordForCreateUserEnabled()
        );
        $this->assertSame(
            true,
            $registerSettings->isForceLoginAfterCreateUserEnabled()
        );
        $this->assertSame(
            'test',
            $registerSettings->getAllowedUserMeta()
        );
    }

    public function testValidation()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('New User profile slug can not be empty.');
        $registerUser = (new RegisterSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost([]);
        $registerUser->initSettingsFromPost();
        $registerUser->validateSettings();
    }
}