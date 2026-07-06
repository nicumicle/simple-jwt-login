<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Migrations;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\Migrations\SettingsMigrationService;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class SettingsMigrationServiceTest extends TestCase
{
    public function testMigratesV1ToV2(): void
    {
        $v1Settings = ['allow_autologin' => '1', 'allow_authentication' => '1'];
        $result = SettingsMigrationService::migrate($v1Settings);

        $this->assertSame(SimpleJWTLoginSettings::SCHEMA_VERSION, $result['_schema_version']);
        $this->assertArrayHasKey('login', $result);
        $this->assertSame('1', $result['login']['enabled']);
        $this->assertArrayHasKey('authorization', $result);
        $this->assertSame('1', $result['authorization']['enabled']);
    }

    public function testAlreadyV2DataIsNotCorrupted(): void
    {
        $v2Settings = [
            '_schema_version' => 2,
            'login'           => ['enabled' => '1', 'auth_code' => false],
            'authorization'   => ['enabled' => false, 'ttl' => 60],
        ];

        $result = SettingsMigrationService::migrate($v2Settings);

        $this->assertSame(2, $result['_schema_version']);
        $this->assertSame('1', $result['login']['enabled']);
        $this->assertSame(60, $result['authorization']['ttl']);
    }

    public function testEmptyArrayProducesV2Structure(): void
    {
        $result = SettingsMigrationService::migrate([]);
        $this->assertSame(SimpleJWTLoginSettings::SCHEMA_VERSION, $result['_schema_version']);
        $this->assertArrayHasKey('login', $result);
        $this->assertArrayHasKey('register', $result);
        $this->assertArrayHasKey('authorization', $result);
    }

    public function testNullInputProducesV2Structure(): void
    {
        $result = SettingsMigrationService::migrate(null);
        $this->assertSame(SimpleJWTLoginSettings::SCHEMA_VERSION, $result['_schema_version']);
    }

    public function testPartialV1DataMigratedWithoutThrow(): void
    {
        $partial = ['allow_register' => '1'];
        $result  = SettingsMigrationService::migrate($partial);

        $this->assertSame(SimpleJWTLoginSettings::SCHEMA_VERSION, $result['_schema_version']);
        $this->assertSame('1', $result['register']['enabled']);
        $this->assertArrayHasKey('login', $result);
        $this->assertSame([], $result['login']);
    }
}
