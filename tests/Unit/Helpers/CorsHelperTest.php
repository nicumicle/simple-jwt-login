<?php

namespace SimpleJwtLoginTests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\CorsHelper;

class CorsHelperTest extends TestCase
{
    public function testAddHeader()
    {
        $corsHelper = new CorsHelper();
        $headerName = 'TEST';
        $headerValue = '123';
        $this->expectNotToPerformAssertions();
        $corsHelper->addHeader($headerName, $headerValue);
    }
}
