<?php

namespace SimpleJwtLoginTests\Feature\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use SimpleJwtLoginTests\Feature\FeatureTestCase;
use SimpleJwtLoginTests\Feature\MysqliWpdb;

/**
 * Expiry dates are stored in UTC (gmdate). WordPress never sets the MySQL session time zone,
 * so the expiry checks must use the UTC clock of the database, whatever its time zone is.
 */
class RepositoryClockTest extends FeatureTestCase
{
    const USER_ID = 987654;

    /**
     * @var MysqliWpdb
     */
    private $wpdb;

    public function setUp(): void
    {
        parent::setUp();
        $this->wpdb = new MysqliWpdb(self::$dbCon, self::getTablePrefix());
        (new RefreshTokenRepository($this->wpdb))->createTable();
        (new RevokedTokenRepository($this->wpdb))->createTable();
        (new ApiKeyRepository($this->wpdb))->createTable();
    }

    public function tearDown(): void
    {
        self::$dbCon->query('SET time_zone = @@global.time_zone');
        (new RefreshTokenRepository($this->wpdb))->deleteByUserId(self::USER_ID);
        (new RevokedTokenRepository($this->wpdb))->deleteByUserId(self::USER_ID);
        self::$dbCon->query(
            'DELETE FROM `' . self::getTablePrefix() . 'simple_jwt_login_api_keys` WHERE user_id = ' . self::USER_ID
        );
        parent::tearDown();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function timeZoneProvider()
    {
        return [
            'database ahead of UTC' => ['+05:00'],
            'database behind UTC'   => ['-05:00'],
        ];
    }

    #[DataProvider('timeZoneProvider')]
    public function testRefreshTokenExpiryUsesUtc($timeZone)
    {
        $this->setDatabaseTimeZone($timeZone);
        $repository = new RefreshTokenRepository($this->wpdb);
        $repository->insert(self::USER_ID, 'clock-valid-' . $timeZone, time() + 3600);
        $repository->insert(self::USER_ID, 'clock-expired-' . $timeZone, time() - 60);

        $this->assertNotNull($repository->getByToken('clock-valid-' . $timeZone));
        $this->assertNull($repository->getByToken('clock-expired-' . $timeZone));
    }

    #[DataProvider('timeZoneProvider')]
    public function testRefreshTokenCleanupKeepsValidTokens($timeZone)
    {
        $this->setDatabaseTimeZone($timeZone);
        $repository = new RefreshTokenRepository($this->wpdb);
        $repository->insert(self::USER_ID, 'cleanup-valid-' . $timeZone, time() + 3600);

        $repository->cleanupExpired();

        $this->assertNotNull($repository->getByToken('cleanup-valid-' . $timeZone));
    }

    /**
     * A revoked JWT must stay revoked until it really expires: cleanup that runs early
     * makes the JWT valid again.
     */
    #[DataProvider('timeZoneProvider')]
    public function testRevokedTokenIsKeptUntilItExpires($timeZone)
    {
        $this->setDatabaseTimeZone($timeZone);
        $repository = new RevokedTokenRepository($this->wpdb);
        $repository->insert(self::USER_ID, 'revoked-valid-' . $timeZone, gmdate('Y-m-d H:i:s', time() + 3600));
        $repository->insert(self::USER_ID, 'revoked-expired-' . $timeZone, gmdate('Y-m-d H:i:s', time() - 60));

        $repository->deleteExpired();

        $this->assertTrue($repository->existsForUser(self::USER_ID, 'revoked-valid-' . $timeZone));
        $this->assertFalse($repository->existsForUser(self::USER_ID, 'revoked-expired-' . $timeZone));
    }

    #[DataProvider('timeZoneProvider')]
    public function testApiKeyExpiryUsesUtc($timeZone)
    {
        $this->setDatabaseTimeZone($timeZone);
        $repository = new ApiKeyRepository($this->wpdb);
        $now = gmdate('Y-m-d H:i:s');
        $validHash = hash('sha256', 'valid' . $timeZone);
        $expiredHash = hash('sha256', 'expired' . $timeZone);
        $repository->insert(self::USER_ID, 'valid', $validHash, 'sjl_v', '["read"]', gmdate('Y-m-d H:i:s', time() + 3600), $now);
        $repository->insert(self::USER_ID, 'expired', $expiredHash, 'sjl_e', '["read"]', gmdate('Y-m-d H:i:s', time() - 60), $now);

        $this->assertNotNull($repository->getByKeyHash($validHash));
        $this->assertNull($repository->getByKeyHash($expiredHash));
    }

    /**
     * @param string $timeZone
     * @return void
     */
    private function setDatabaseTimeZone($timeZone)
    {
        self::$dbCon->query("SET time_zone = '" . self::$dbCon->real_escape_string($timeZone) . "'");
    }
}
