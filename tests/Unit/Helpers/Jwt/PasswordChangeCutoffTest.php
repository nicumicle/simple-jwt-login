<?php

namespace SimpleJwtLoginTests\Unit\Helpers\Jwt;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\Jwt\PasswordChangeCutoff;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository;

class PasswordChangeCutoffTest extends TestCase
{
    /**
     * @param array<string, int> $meta meta key => cutoff timestamp
     */
    #[DataProvider('revokedProvider')]
    public function testIsRevoked(bool $revokeOnChange, array $meta, int $issuedAt, bool $expected): void
    {
        $wordPressData = $this->createStub(Repository::class);
        $metaMap = [];
        foreach ([PasswordChangeCutoff::META_KEY, PasswordChangeCutoff::REVOKE_ALL_META_KEY] as $metaKey) {
            $metaMap[] = [1, $metaKey, isset($meta[$metaKey]) ? [(string) $meta[$metaKey]] : []];
        }
        $wordPressData->method('getUserMeta')->willReturnMap($metaMap);
        $authSettings = (new AuthenticationSettings())->withSettings(
            ['authorization' => ['revoke_on_password_change' => $revokeOnChange]]
        );

        $this->assertSame(
            $expected,
            PasswordChangeCutoff::isRevokedByPasswordChange($authSettings, $wordPressData, 1, ['iat' => $issuedAt])
        );
    }

    public static function revokedProvider(): array
    {
        $all = PasswordChangeCutoff::REVOKE_ALL_META_KEY;
        $password = PasswordChangeCutoff::META_KEY;

        return [
            'no cutoff'                              => [true, [], 1000, false],
            'revoke-all applies with setting off'    => [false, [$all => 1000], 999, true],
            'revoke-all includes the same second'    => [false, [$all => 1000], 1000, true],
            'issued after revoke-all'                => [false, [$all => 1000], 1001, false],
            'password cutoff ignored with setting off' => [false, [$password => 1000], 999, false],
            'password cutoff with setting on'        => [true, [$password => 1000], 999, true],
            'password cutoff excludes the same second' => [true, [$password => 1000], 1000, false],
        ];
    }

    public function testMarkAllRevokedStoresTheCurrentTime(): void
    {
        $wordPressData = $this->createMock(Repository::class);
        $wordPressData->expects($this->once())
            ->method('updateUserMeta')
            ->with(7, PasswordChangeCutoff::REVOKE_ALL_META_KEY, $this->callback(function ($value) {
                return abs((int) $value - time()) <= 1;
            }));

        PasswordChangeCutoff::markAllRevoked($wordPressData, 7);
    }
}
