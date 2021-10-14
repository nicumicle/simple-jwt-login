<?php

declare(strict_types=1);

namespace SimpleJwtLoginTests\Helpers;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\Sanitizer;
use Exception;

class SanitizerTest extends TestCase
{
    /**
     * @param mixed $text
     * @param mixed $expectedResult
     * @throws Exception
     *
     * @dataProvider pathsDataProvider
     */
    public function testPathSanitization($text, $expectedResult){
        $this->assertSame(
            $expectedResult,
            Sanitizer::path($text)
        );
    }

    public function pathsDataProvider()
    {
        return [
            [
                'wp_config.php',
                'wp_config.php'
            ],
            [
                '../wp-config.php',
                'wp-config.php'
            ],
            [
                '../../../../../../.././wp-config.php',
                'wp-config.php'
            ],
            [
                '////////wp-config.php',
                'wp-config.php',
            ],
            [
                '.\\\..\\\wp-config.php',
                'wp-config.php',
            ],
            [
                '\wpconfig.php',
                'wpconfig.php',
            ]
        ];
    }

    /**
     * @param mixed $text
     * @param mixed $expectedResult
     * @throws Exception
     *
     * @dataProvider invalidPathDataProvider
     */
    public function testInvalidPathsSanitization($text){
        $this->expectException(Exception::class);
        Sanitizer::path($text);
    }

    public function invalidPathDataProvider()
    {
        return [
            [
                '../'
            ],
            [
                '',
            ],
            [
                '../.env'
            ],
            [
                '/.env'
            ],
            [
                'wp-config' //##NO PHP extension
            ]
        ];
    }
}