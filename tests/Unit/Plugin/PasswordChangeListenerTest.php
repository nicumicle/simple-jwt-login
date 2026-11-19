<?php

namespace SimpleJwtLoginTests\Unit\Plugin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\Jwt\PasswordChangeCutoff;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Plugin\PasswordChangeListener;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressRepositoryInterface;
use stdClass;

class PasswordChangeListenerTest extends TestCase
{
    private function authSettings(bool $enabled): AuthenticationSettings
    {
        $authSettings = $this->createStub(AuthenticationSettings::class);
        $authSettings->method('isRevokeOnPasswordChangeEnabled')->willReturn($enabled);

        return $authSettings;
    }

    public function testPasswordResetRevokesTheUserTokens(): void
    {
        $user          = new stdClass();
        $wordPressData = $this->createMock(WordPressRepositoryInterface::class);
        $wordPressData->method('getUserProperty')->willReturn('7');
        $wordPressData->expects($this->once())
            ->method('updateUserMeta')
            ->with(7, PasswordChangeCutoff::META_KEY, $this->callback(
                static fn ($value): bool => abs((int) $value - time()) <= 1
            ));
        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->expects($this->once())->method('deleteByUserId')->with(7);

        (new PasswordChangeListener($wordPressData, $refreshTokens, $this->authSettings(true)))->onPasswordReset($user);
    }

    /**
     * wp_set_password() fires neither after_password_reset nor profile_update
     * (e.g. the Two Factor plugin's compromised-password reset, WP-CLI).
     */
    public function testWpSetPasswordRevokesTheUserTokens(): void
    {
        $wordPressData = $this->createMock(WordPressRepositoryInterface::class);
        $wordPressData->expects($this->once())
            ->method('updateUserMeta')
            ->with(11, PasswordChangeCutoff::META_KEY, $this->anything());
        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->expects($this->once())->method('deleteByUserId')->with(11);

        (new PasswordChangeListener($wordPressData, $refreshTokens, $this->authSettings(true)))
            ->onPasswordSet('new-plain-password', '11');
    }

    /**
     * @return array<string, array{array<string, string>, bool}>
     */
    public static function profileUpdateProvider(): array
    {
        return [
            'password hash changed'         => [['user_pass' => 'new-hash'], true],
            'password hash unchanged'       => [['user_pass' => 'old-hash'], false],
            'no user data (WordPress < 5.8)' => [[], false],
        ];
    }

    /**
     * @param array<string, string> $userData
     */
    #[DataProvider('profileUpdateProvider')]
    public function testProfileUpdateRevokesTokensOnlyWhenThePasswordChanged(array $userData, bool $revoked): void
    {
        $wordPressData = $this->createMock(WordPressRepositoryInterface::class);
        $wordPressData->method('getUserProperty')->willReturn('old-hash');
        $wordPressData->expects($revoked ? $this->once() : $this->never())->method('updateUserMeta');
        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->expects($revoked ? $this->once() : $this->never())->method('deleteByUserId')->with(9);

        (new PasswordChangeListener($wordPressData, $refreshTokens, $this->authSettings(true)))
            ->onProfileUpdate(9, new stdClass(), $userData);
    }

    /**
     * With the option off a password change revokes nothing (the behaviour before the option existed).
     */
    public function testNothingIsRevokedWhenTheOptionIsOff(): void
    {
        $wordPressData = $this->createMock(WordPressRepositoryInterface::class);
        $wordPressData->method('getUserProperty')->willReturn('old-hash');
        $wordPressData->expects($this->never())->method('updateUserMeta');
        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->expects($this->never())->method('deleteByUserId');
        $listener = new PasswordChangeListener($wordPressData, $refreshTokens, $this->authSettings(false));

        $listener->onPasswordReset(new stdClass());
        $listener->onProfileUpdate(9, new stdClass(), ['user_pass' => 'new-hash']);
    }

    /**
     * @return array<string, array{mixed, array<string, int>, bool}>
     */
    public static function cutoffProvider(): array
    {
        return [
            'password never changed'          => [[], ['iat' => 100], false],
            'issued before the change'        => [['200'], ['iat' => 199], true],
            'issued in the second of the change' => [['200'], ['iat' => 200], false],
            'issued after the change'         => [['200'], ['iat' => 300], false],
            'no iat after a change'           => [['200'], [], true],
        ];
    }

    /**
     * @param mixed $stored
     * @param array<string, int> $payload
     */
    #[DataProvider('cutoffProvider')]
    public function testIsIssuedBeforePasswordChange($stored, array $payload, bool $expected): void
    {
        $wordPressData = $this->createStub(WordPressRepositoryInterface::class);
        $wordPressData->method('getUserMeta')->willReturn($stored);

        $this->assertSame(
            $expected,
            PasswordChangeCutoff::isIssuedBeforePasswordChange($wordPressData, 3, $payload)
        );
    }
}
