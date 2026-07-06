<?php

namespace SimpleJwtLoginTests\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Plugin\RevokedTokenMigrator;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressRepositoryInterface;

class RevokedTokenMigratorTest extends TestCase
{
    private function buildJwt($exp = null)
    {
        $payload = $exp === null ? [] : ['exp' => $exp];
        $segment = rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');

        return 'header.' . $segment . '.signature';
    }

    public function testMigrateSkipsAlreadyExpiredToken()
    {
        $wordPressData = $this->createStub(WordPressRepositoryInterface::class);
        $wordPressData->method('getUserIdsWithMeta')->willReturn([5]);
        $expiredJwt = $this->buildJwt(time() - 3600);
        $wordPressData->method('getUserMeta')->willReturn([$expiredJwt]);

        $revokedTokenRepo = $this->createMock(RevokedTokenRepositoryInterface::class);
        $revokedTokenRepo->expects($this->never())->method('insert');

        $migrator = new RevokedTokenMigrator($wordPressData, $revokedTokenRepo);
        $migrator->migrate();
    }

    public function testMigrateSkipsTokenAlreadyMigrated()
    {
        $wordPressData = $this->createStub(WordPressRepositoryInterface::class);
        $wordPressData->method('getUserIdsWithMeta')->willReturn([5]);
        $validJwt = $this->buildJwt(time() + 3600);
        $wordPressData->method('getUserMeta')->willReturn([$validJwt]);

        $revokedTokenRepo = $this->createMock(RevokedTokenRepositoryInterface::class);
        $revokedTokenRepo->method('existsForUser')->willReturn(true);
        $revokedTokenRepo->expects($this->never())->method('insert');

        $migrator = new RevokedTokenMigrator($wordPressData, $revokedTokenRepo);
        $migrator->migrate();
    }

    public function testMigrateInsertsValidTokenAndDeletesUserMeta()
    {
        $wordPressData = $this->createMock(WordPressRepositoryInterface::class);
        $wordPressData->method('getUserIdsWithMeta')->willReturn([5]);
        $validJwt = $this->buildJwt(time() + 3600);
        $wordPressData->method('getUserMeta')->willReturn([$validJwt]);
        $wordPressData->expects($this->once())
            ->method('deleteUserMeta')
            ->with(5, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY, '');

        $revokedTokenRepo = $this->createMock(RevokedTokenRepositoryInterface::class);
        $revokedTokenRepo->method('existsForUser')->willReturn(false);
        $revokedTokenRepo->expects($this->once())
            ->method('insert')
            ->with(5, hash('sha256', $validJwt), $this->isString());

        $migrator = new RevokedTokenMigrator($wordPressData, $revokedTokenRepo);
        $migrator->migrate();
    }

    public function testMigrateSkipsUsersWithNoTokens()
    {
        $wordPressData = $this->createMock(WordPressRepositoryInterface::class);
        $wordPressData->method('getUserIdsWithMeta')->willReturn([5]);
        $wordPressData->method('getUserMeta')->willReturn([]);
        $wordPressData->expects($this->never())->method('deleteUserMeta');

        $revokedTokenRepo = $this->createMock(RevokedTokenRepositoryInterface::class);
        $revokedTokenRepo->expects($this->never())->method('insert');

        $migrator = new RevokedTokenMigrator($wordPressData, $revokedTokenRepo);
        $migrator->migrate();
    }
}
