<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Migrations;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\Migrations\V2ToV3Migration;

class V2ToV3MigrationTest extends TestCase
{
    public function testVersionNumbers(): void
    {
        $migration = new V2ToV3Migration();
        $this->assertSame(2, $migration->getSourceVersion());
        $this->assertSame(3, $migration->getTargetVersion());
    }

    #[DataProvider('payloadProvider')]
    public function testJtiIsAddedToTheSavedPayload(array $settings, array $expectedAuth): void
    {
        $result = (new V2ToV3Migration())->migrate($settings);

        $this->assertSame(3, $result['_schema_version']);
        $this->assertSame($expectedAuth, isset($result['authorization']) ? $result['authorization'] : []);
    }

    public static function payloadProvider(): array
    {
        return [
            'saved payload gets jti' => [
                ['_schema_version' => 2, 'authorization' => ['ttl' => 60, 'jwt_payload' => ['exp', 'email']]],
                ['ttl' => 60, 'jwt_payload' => ['exp', 'email', 'jti']],
            ],
            'jti already in the payload is not duplicated' => [
                ['_schema_version' => 2, 'authorization' => ['jwt_payload' => ['jti', 'exp']]],
                ['jwt_payload' => ['jti', 'exp']],
            ],
            'payload never saved stays unset (jti is on by default)' => [
                ['_schema_version' => 2, 'authorization' => ['ttl' => 60]],
                ['ttl' => 60],
            ],
            'no authorization section' => [
                ['_schema_version' => 2],
                [],
            ],
        ];
    }

    public function testOtherSectionsAreKept(): void
    {
        $result = (new V2ToV3Migration())->migrate([
            '_schema_version' => 2,
            'login'           => ['enabled' => '1'],
        ]);

        $this->assertSame(['enabled' => '1'], $result['login']);
    }
}
