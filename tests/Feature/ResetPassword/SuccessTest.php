<?php

namespace SimpleJwtLoginTests\Feature\ResetPassword;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

class SuccessTest extends FeatureTestCase
{
    const JWT_SECRET_KEY = 'test';

    /**
     * @return array<string,mixed>
     */
    private static function baseSettings(): array
    {
        return [
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_refresh_ttl'    => '20160',
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'auth_password_base64'    => false,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => self::JWT_SECRET_KEY,
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'register_ip'             => '',
            'register_domain'         => '',
            'require_register_auth'   => false,
            'allow_delete'            => true,
            'require_delete_auth'     => false,
            'delete_ip'               => '',
            'delete_user_by'          => 0,
            'jwt_delete_by_parameter' => 'email',
            'jwt_login_by'            => 0,
            'jwt_login_by_parameter'  => 'email',
            'allow_reset_password'                => true,
            'reset_password_requires_auth_code'   => false,
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'jwt_reset_password_flow' => ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB,
            'reset_password_jwt'      => true,
        ]));
    }

    #[TestDox('Reset-password request succeeds with FLOW_JUST_SAVE_IN_DB')]
    public function testSendResetPasswordWithSaveInDbFlow(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/user/reset_password', [
            'email' => $email,
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'reset password request failed');
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertSame('The Code has been saved into the database.', $body['data']['message']);

        [$deleteStatus] = $this->deleteUser($this->getJWTForUser($email, $password));
        $this->assertSame(200, $deleteStatus, 'cleanup failed');
    }

    #[TestDox('Reset-password request succeeds with FLOW_SEND_DEFAULT_WP_EMAIL')]
    public function testSendResetPasswordWithWpEmailFlow(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'jwt_reset_password_flow' => ResetPasswordSettings::FLOW_SEND_DEFAULT_WP_EMAIL,
            'reset_password_jwt'      => false,
        ]));

        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/user/reset_password', [
            'email' => $email,
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'reset password request failed');
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertSame('Reset password email has been sent.', $body['data']['message']);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'jwt_reset_password_flow' => ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB,
            'reset_password_jwt'      => true,
        ]));

        [$deleteStatus] = $this->deleteUser($this->getJWTForUser($email, $password));
        $this->assertSame(200, $deleteStatus, 'cleanup failed');
    }

    #[TestDox('Password can be changed using a valid JWT')]
    public function testChangePasswordWithJwt(): void
    {
        [$email, , $status] = $this->createUser(['user_pass' => 'OldPass1234!']);
        $this->assertSame(200, $status, 'register failed');

        $jwt = $this->getJWTForUser($email, 'OldPass1234!');
        $this->assertNotNull($jwt);

        $response = $this->jsonRequest('PUT', '/simple-jwt-login/v1/user/reset_password', [
            'email'        => $email,
            'new_password' => 'NewPass5678!',
            'JWT'          => $jwt,
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'change password failed');
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertSame('User Password has been changed.', $body['data']['message']);

        [$authStatus] = $this->authUser($email, 'NewPass5678!');
        $this->assertSame(200, $authStatus, 'auth with new password failed');

        $newJwt = $this->getJWTForUser($email, 'NewPass5678!');
        [$deleteStatus] = $this->deleteUser($newJwt);
        $this->assertSame(200, $deleteStatus, 'cleanup failed');
    }
}
