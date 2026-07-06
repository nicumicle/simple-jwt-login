<?php

namespace SimpleJwtLoginTests\Unit\Modules;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;

class SimpleJWTLoginHooksTest extends TestCase
{
    #[DataProvider('hooksProvider')]
    public function testHookHasRequiredFields(array $hook): void
    {
        $this->assertArrayHasKey('name', $hook);
        $this->assertArrayHasKey('type', $hook);
        $this->assertArrayHasKey('parameters', $hook);
        $this->assertArrayHasKey('description', $hook);
    }

    /**
     * @return array<string, array{array}>
     */
    public static function hooksProvider(): array
    {
        $cases = [];
        foreach (SimpleJWTLoginHooks::getHooksDetails() as $hook) {
            $cases[$hook['name']] = [$hook];
        }
        return $cases;
    }
}
