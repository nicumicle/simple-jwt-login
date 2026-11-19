<?php

namespace SimpleJwtLoginTests\Feature\RegisterUsers;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Registration must not let the client set account fields that WordPress manages itself:
 * a planted user_activation_key is a password reset code the registrant chose.
 */
class ProtectedUserFieldsTest extends FeatureTestCase
{
    private const PLANTED_KEY = '1700000000:$P$Bplantedresetkeyhash0000000000.';

    private const PLANTED_DATE = '2001-01-01 00:00:00';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'register' => ['enabled' => true, 'new_user_profile' => 'subscriber'],
        ]));
    }

    #[TestDox('Register ignores user_activation_key, user_registered and spam from the request')]
    public function testRegisterIgnoresWordPressManagedFields(): void
    {
        $email    = 'protected-' . uniqid() . '@example.com';
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
            'email'               => $email,
            'user_login'          => md5($email),
            'password'            => 'Secret1234!',
            'user_activation_key' => self::PLANTED_KEY,
            'user_registered'     => self::PLANTED_DATE,
            'spam'                => '1',
            'display_name'        => 'Allowed Name',
        ]);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $table   = self::getTablePrefix() . 'users';
        $escaped = self::$dbCon->real_escape_string($email);
        $row     = self::$dbCon->query(
            "SELECT user_activation_key, user_registered, display_name FROM `{$table}` WHERE user_email = '{$escaped}'"
        )->fetch_assoc();

        $this->assertNotSame(self::PLANTED_KEY, $row['user_activation_key']);
        $this->assertNotSame(self::PLANTED_DATE, $row['user_registered']);
        // Fields that are meant to be set on register still work.
        $this->assertSame('Allowed Name', $row['display_name']);
    }
}
