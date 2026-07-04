<?php

namespace SimpleJwtLoginTests\Unit\Modules;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\AuditEvents;

class AuditEventsTest extends TestCase
{
    public function testAllReturnsExpectedEvents()
    {
        $events = AuditEvents::all();

        $this->assertCount(32, $events);
    }

    #[DataProvider('allEventsProvider')]
    public function testAllContainsEvent(string $event): void
    {
        $this->assertContains($event, AuditEvents::all());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allEventsProvider(): array
    {
        return [
            'auth_login_success'          => [AuditEvents::AUTH_LOGIN_SUCCESS],
            'auth_login_failed'           => [AuditEvents::AUTH_LOGIN_FAILED],
            'auth_logout_success'         => [AuditEvents::AUTH_LOGOUT_SUCCESS],
            'auth_logout_failed'          => [AuditEvents::AUTH_LOGOUT_FAILED],
            'auth_register_success'       => [AuditEvents::AUTH_REGISTER_SUCCESS],
            'auth_register_failed'        => [AuditEvents::AUTH_REGISTER_FAILED],
            'auth_password_reset_request' => [AuditEvents::AUTH_PASSWORD_RESET_REQUEST],
            'auth_password_reset_success' => [AuditEvents::AUTH_PASSWORD_RESET_SUCCESS],
            'auth_password_reset_failed'  => [AuditEvents::AUTH_PASSWORD_RESET_FAILED],
            'auth_delete_user_success'    => [AuditEvents::AUTH_DELETE_USER_SUCCESS],
            'auth_delete_user_failed'     => [AuditEvents::AUTH_DELETE_USER_FAILED],
            'auth_login_session_success'  => [AuditEvents::AUTH_LOGIN_SESSION_SUCCESS],
            'auth_login_session_failed'   => [AuditEvents::AUTH_LOGIN_SESSION_FAILED],
            'auth_refresh_token_success'  => [AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS],
            'auth_refresh_token_failed'   => [AuditEvents::AUTH_REFRESH_TOKEN_FAILED],
            'auth_oauth_success'          => [AuditEvents::AUTH_OAUTH_SUCCESS],
            'auth_oauth_failed'           => [AuditEvents::AUTH_OAUTH_FAILED],
            'auth_2fa_challenge_issued'   => [AuditEvents::AUTH_2FA_CHALLENGE_ISSUED],
            'auth_2fa_verify_success'     => [AuditEvents::AUTH_2FA_VERIFY_SUCCESS],
            'auth_2fa_verify_failed'      => [AuditEvents::AUTH_2FA_VERIFY_FAILED],
            'settings_save_success'       => [AuditEvents::SETTINGS_SAVE_SUCCESS],
            'api_key_create_success'      => [AuditEvents::API_KEY_CREATE_SUCCESS],
            'api_key_create_failed'       => [AuditEvents::API_KEY_CREATE_FAILED],
            'api_key_update_success'      => [AuditEvents::API_KEY_UPDATE_SUCCESS],
            'api_key_update_failed'       => [AuditEvents::API_KEY_UPDATE_FAILED],
            'api_key_revoke_success'      => [AuditEvents::API_KEY_REVOKE_SUCCESS],
            'api_key_revoke_failed'       => [AuditEvents::API_KEY_REVOKE_FAILED],
            'api_key_delete_success'      => [AuditEvents::API_KEY_DELETE_SUCCESS],
            'api_key_delete_failed'       => [AuditEvents::API_KEY_DELETE_FAILED],
            'api_key_used'                => [AuditEvents::API_KEY_USED],
            'revoked_token_delete_success' => [AuditEvents::REVOKED_TOKEN_DELETE_SUCCESS],
            'revoked_token_delete_failed'  => [AuditEvents::REVOKED_TOKEN_DELETE_FAILED],
        ];
    }

    #[DataProvider('constantValuesProvider')]
    public function testConstantValue(string $expected, string $actual): void
    {
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function constantValuesProvider(): array
    {
        return [
            'AUTH_LOGIN_SUCCESS'          => ['auth.login.success',          AuditEvents::AUTH_LOGIN_SUCCESS],
            'AUTH_LOGIN_FAILED'           => ['auth.login.failed',           AuditEvents::AUTH_LOGIN_FAILED],
            'AUTH_LOGOUT_SUCCESS'         => ['auth.logout.success',         AuditEvents::AUTH_LOGOUT_SUCCESS],
            'AUTH_LOGOUT_FAILED'          => ['auth.logout.failed',          AuditEvents::AUTH_LOGOUT_FAILED],
            'AUTH_REGISTER_SUCCESS'       => ['auth.register.success',       AuditEvents::AUTH_REGISTER_SUCCESS],
            'AUTH_REGISTER_FAILED'        => ['auth.register.failed',        AuditEvents::AUTH_REGISTER_FAILED],
            'AUTH_PASSWORD_RESET_REQUEST' => ['auth.password_reset.request', AuditEvents::AUTH_PASSWORD_RESET_REQUEST],
            'AUTH_PASSWORD_RESET_SUCCESS' => ['auth.password_reset.success', AuditEvents::AUTH_PASSWORD_RESET_SUCCESS],
            'AUTH_PASSWORD_RESET_FAILED'  => ['auth.password_reset.failed',  AuditEvents::AUTH_PASSWORD_RESET_FAILED],
            'AUTH_DELETE_USER_SUCCESS'    => ['auth.delete_user.success',    AuditEvents::AUTH_DELETE_USER_SUCCESS],
            'AUTH_DELETE_USER_FAILED'     => ['auth.delete_user.failed',     AuditEvents::AUTH_DELETE_USER_FAILED],
            'AUTH_LOGIN_SESSION_SUCCESS'  => ['auth.login_session.success',  AuditEvents::AUTH_LOGIN_SESSION_SUCCESS],
            'AUTH_LOGIN_SESSION_FAILED'   => ['auth.login_session.failed',   AuditEvents::AUTH_LOGIN_SESSION_FAILED],
            'AUTH_REFRESH_TOKEN_SUCCESS'  => ['auth.refresh_token.success',  AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS],
            'AUTH_REFRESH_TOKEN_FAILED'   => ['auth.refresh_token.failed',   AuditEvents::AUTH_REFRESH_TOKEN_FAILED],
            'AUTH_OAUTH_SUCCESS'          => ['auth.oauth.success',           AuditEvents::AUTH_OAUTH_SUCCESS],
            'AUTH_OAUTH_FAILED'           => ['auth.oauth.failed',            AuditEvents::AUTH_OAUTH_FAILED],
            'AUTH_2FA_CHALLENGE_ISSUED'   => ['auth.2fa.challenge_issued',    AuditEvents::AUTH_2FA_CHALLENGE_ISSUED],
            'AUTH_2FA_VERIFY_SUCCESS'     => ['auth.2fa.verify_success',      AuditEvents::AUTH_2FA_VERIFY_SUCCESS],
            'AUTH_2FA_VERIFY_FAILED'      => ['auth.2fa.verify_failed',       AuditEvents::AUTH_2FA_VERIFY_FAILED],
            'SETTINGS_SAVE_SUCCESS'       => ['settings.save.success',        AuditEvents::SETTINGS_SAVE_SUCCESS],
            'API_KEY_CREATE_SUCCESS'      => ['api_key.create.success',      AuditEvents::API_KEY_CREATE_SUCCESS],
            'API_KEY_CREATE_FAILED'       => ['api_key.create.failed',       AuditEvents::API_KEY_CREATE_FAILED],
            'API_KEY_UPDATE_SUCCESS'      => ['api_key.update.success',      AuditEvents::API_KEY_UPDATE_SUCCESS],
            'API_KEY_UPDATE_FAILED'       => ['api_key.update.failed',       AuditEvents::API_KEY_UPDATE_FAILED],
            'API_KEY_REVOKE_SUCCESS'      => ['api_key.revoke.success',      AuditEvents::API_KEY_REVOKE_SUCCESS],
            'API_KEY_REVOKE_FAILED'       => ['api_key.revoke.failed',       AuditEvents::API_KEY_REVOKE_FAILED],
            'API_KEY_DELETE_SUCCESS'      => ['api_key.delete.success',      AuditEvents::API_KEY_DELETE_SUCCESS],
            'API_KEY_DELETE_FAILED'       => ['api_key.delete.failed',       AuditEvents::API_KEY_DELETE_FAILED],
            'API_KEY_USED'                => ['api_key.used',                AuditEvents::API_KEY_USED],
            'REVOKED_TOKEN_DELETE_SUCCESS' => ['revoked_token.delete.success', AuditEvents::REVOKED_TOKEN_DELETE_SUCCESS],
            'REVOKED_TOKEN_DELETE_FAILED'  => ['revoked_token.delete.failed',  AuditEvents::REVOKED_TOKEN_DELETE_FAILED],
        ];
    }

    public function testLabelsReturnsExpectedEntries()
    {
        $labels = AuditEvents::labels();

        $this->assertCount(32, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_LOGIN_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_LOGIN_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_LOGOUT_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_REGISTER_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_PASSWORD_RESET_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_DELETE_USER_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_DELETE_USER_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_LOGIN_SESSION_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_LOGIN_SESSION_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_REFRESH_TOKEN_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_OAUTH_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_OAUTH_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_2FA_CHALLENGE_ISSUED, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_2FA_VERIFY_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::AUTH_2FA_VERIFY_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::SETTINGS_SAVE_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::API_KEY_CREATE_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::API_KEY_CREATE_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::API_KEY_UPDATE_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::API_KEY_UPDATE_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::API_KEY_REVOKE_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::API_KEY_REVOKE_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::API_KEY_DELETE_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::API_KEY_DELETE_FAILED, $labels);
        $this->assertArrayHasKey(AuditEvents::API_KEY_USED, $labels);
        $this->assertArrayHasKey(AuditEvents::REVOKED_TOKEN_DELETE_SUCCESS, $labels);
        $this->assertArrayHasKey(AuditEvents::REVOKED_TOKEN_DELETE_FAILED, $labels);
    }
}
