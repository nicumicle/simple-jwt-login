<?php


namespace SimpleJwtLoginTests\Unit\Modules;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;

class SimpleJWTLoginHooksTest extends TestCase
{
    public function testGetHooksDetails()
	{
        $result = SimpleJWTLoginHooks::getHooksDetails();
        foreach ($result as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('parameters', $item);
            $this->assertArrayHasKey('description', $item);
        }
    }
}
