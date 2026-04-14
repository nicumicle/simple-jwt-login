<?php

namespace SimpleJwtLoginTests\Unit\Modules;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\AuditEvents;

class AuditEventsTest extends TestCase
{
    public function testAllReturnsEighteenEvents()
    {
        $events = AuditEvents::all();

        $this->assertCount(18, $events);
    }

    public function testAllContainsExpectedConstants()
    {
        $events = AuditEvents::all();

        $this->assertContains(AuditEvents::AUTH_LOGIN_SUCCESS, $events);
        $this->assertContains(AuditEvents::AUTH_LOGIN_FAILED, $events);
        $this->assertContains(AuditEvents::AUTH_LOGOUT_SUCCESS, $events);
        $this->assertContains(AuditEvents::AUTH_LOGOUT_FAILED, $events);
        $this->assertContains(AuditEvents::AUTH_REGISTER_SUCCESS, $events);
        $this->assertContains(AuditEvents::AUTH_REGISTER_FAILED, $events);
        $this->assertContains(AuditEvents::AUTH_PASSWORD_RESET_REQUEST, $events);
        $this->assertContains(AuditEvents::AUTH_PASSWORD_RESET_SUCCESS, $events);
        $this->assertContains(AuditEvents::AUTH_PASSWORD_RESET_FAILED, $events);
        $this->assertContains(AuditEvents::AUTH_DELETE_USER_SUCCESS, $events);
        $this->assertContains(AuditEvents::AUTH_DELETE_USER_FAILED, $events);
        $this->assertContains(AuditEvents::AUTH_LOGIN_SESSION_SUCCESS, $events);
        $this->assertContains(AuditEvents::AUTH_LOGIN_SESSION_FAILED, $events);
        $this->assertContains(AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS, $events);
        $this->assertContains(AuditEvents::AUTH_REFRESH_TOKEN_FAILED, $events);
        $this->assertContains(AuditEvents::AUTH_OAUTH_SUCCESS, $events);
        $this->assertContains(AuditEvents::AUTH_OAUTH_FAILED, $events);
        $this->assertContains(AuditEvents::SETTINGS_SAVE_SUCCESS, $events);
    }

    public function testConstantValues()
    {
        $this->assertSame('auth.login.success', AuditEvents::AUTH_LOGIN_SUCCESS);
        $this->assertSame('auth.login.failed', AuditEvents::AUTH_LOGIN_FAILED);
        $this->assertSame('auth.logout.success', AuditEvents::AUTH_LOGOUT_SUCCESS);
        $this->assertSame('auth.logout.failed', AuditEvents::AUTH_LOGOUT_FAILED);
        $this->assertSame('auth.register.success', AuditEvents::AUTH_REGISTER_SUCCESS);
        $this->assertSame('auth.register.failed', AuditEvents::AUTH_REGISTER_FAILED);
        $this->assertSame('auth.password_reset.request', AuditEvents::AUTH_PASSWORD_RESET_REQUEST);
        $this->assertSame('auth.password_reset.success', AuditEvents::AUTH_PASSWORD_RESET_SUCCESS);
        $this->assertSame('auth.password_reset.failed', AuditEvents::AUTH_PASSWORD_RESET_FAILED);
        $this->assertSame('auth.delete_user.success', AuditEvents::AUTH_DELETE_USER_SUCCESS);
        $this->assertSame('auth.delete_user.failed', AuditEvents::AUTH_DELETE_USER_FAILED);
        $this->assertSame('auth.login_session.success', AuditEvents::AUTH_LOGIN_SESSION_SUCCESS);
        $this->assertSame('auth.login_session.failed', AuditEvents::AUTH_LOGIN_SESSION_FAILED);
        $this->assertSame('auth.refresh_token.success', AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS);
        $this->assertSame('auth.refresh_token.failed', AuditEvents::AUTH_REFRESH_TOKEN_FAILED);
        $this->assertSame('auth.oauth.success', AuditEvents::AUTH_OAUTH_SUCCESS);
        $this->assertSame('auth.oauth.failed', AuditEvents::AUTH_OAUTH_FAILED);
        $this->assertSame('settings.save.success', AuditEvents::SETTINGS_SAVE_SUCCESS);
    }

    public function testLabelsReturnsEighteenEntries()
    {
        $labels = AuditEvents::labels();

        $this->assertCount(18, $labels);
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
        $this->assertArrayHasKey(AuditEvents::SETTINGS_SAVE_SUCCESS, $labels);
    }
}
