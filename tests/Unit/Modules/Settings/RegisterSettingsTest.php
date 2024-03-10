<?php
namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\RegisterSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class RegisterSettingsTest extends TestCase
{
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
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
            'random_password_length' => '100',
            'register_force_login' => '1',
            'allowed_user_meta' => 'test',
        ];
        $this->wordPressData->method('roleExists')
            ->willReturn(true);
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
            100,
            $registerSettings->getRandomPasswordLength()
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
        $this->wordPressData->method('roleExists')
            ->willReturn(true);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('New User profile slug can not be empty.');
        $registerUser = (new RegisterSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost([]);
        $registerUser->initSettingsFromPost();
        $registerUser->validateSettings();
    }

    #[DataProvider('invalidRoleProvider')]
    /**
     * @param string $role
     * @param string $exception
     * @throws Exception
     */
    public function testInvalidRole($role, $exception)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exception);
        $this->wordPressData->method('roleExists')
            ->willReturn(false);

        $registerUser = (new RegisterSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost([
                'allow_register' => '1',
                'new_user_profile' => $role,
            ]);
        $registerUser->initSettingsFromPost();
        $registerUser->validateSettings();
    }

    #[DataProvider('passwordLengthProvider')]
    /**
     * @param mixed $passwordLength
     * @param string $expectedException
     * @return void
     * @throws Exception
     */
    public function testInvalidPasswordLength($passwordLength, $expectedException)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($expectedException);
        $this->wordPressData->method('roleExists')
            ->willReturn(true);

        $registerUser = (new RegisterSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost([
                'allow_register' => '1',
                'new_user_profile' => 'subscriber',
                'random_password_length' => $passwordLength,
            ]);
        $registerUser->initSettingsFromPost();
        $registerUser->validateSettings();
    }

    /**
     * @return array
     */
    public static function invalidRoleProvider()
    {
        return [
            'empty_role' => [
                'role' => '',
                'exception' => 'New User profile slug can not be empty.',
            ],
            'invalid_role' => [
                'role' => 'test',
                'exception' => 'Invalid user role provided.',
            ]
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function passwordLengthProvider()
    {
        return [
            'one' => [
                'passwordLength' => '1',
                'expectedException' => 'Random password length should be at least 6 characters.',
            ],
            'negative_value' => [
                'passwordLength' => '-1',
                'expectedException' => 'Random password length should be at least 6 characters.',
            ],
            'max_length' => [
                'passwordLength' => '256',
                'expectedException' => 'Random password length can be max 255.',
            ],
            'letters' => [
                'passwordLength' => 'abc',
                'expectedException' => 'Random password length should be an integer.',
            ],
            'letters_and_number' => [
                'passwordLength' => 'abc123',
                'expectedException' => 'Random password length should be an integer.',
            ],
            'number_and_letters' => [
                'passwordLength' => '123abc',
                'expectedException' => 'Random password length should be an integer.',
            ],
            'empty_value' => [
                'passwordLength' => '',
                'expectedException' => 'Random password length should be an integer.',
            ],
            'empty_space' => [
                'passwordLength' => ' ',
                'expectedException' => 'Random password length should be an integer.',
            ],
        ];
    }
}
