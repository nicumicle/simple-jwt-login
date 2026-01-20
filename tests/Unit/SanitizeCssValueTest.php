<?php

namespace SimpleJwtLoginTests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SanitizeCssValueTest extends TestCase
{
    #[DataProvider('validCssValuesProvider')]
    /**
     * Test that valid CSS values pass through correctly
     * @param string $input
     * @param string $expected
     */
    public function testValidCssValues($input, $expected)
    {
        $this->assertSame($expected, simple_jwt_login_sanitize_css_value($input));
    }

    /**
     * @return array[]
     */
    public static function validCssValuesProvider()
    {
        return [
            'hex color' => ['#fff', '#fff'],
            'hex color long' => ['#ffffff', '#ffffff'],
            'color name' => ['red', 'red'],
            'rgb without parens' => ['rgb255,0,0', 'rgb255,0,0'],
            'pixel value' => ['30px', '30px'],
            'em value' => ['1.5em', '1.5em'],
            'percentage' => ['100%', '100%'],
            'border simple' => ['1px solid #ccc', '1px solid #ccc'],
            'empty string' => ['', ''],
        ];
    }

    #[DataProvider('xssAttackVectorsProvider')]
    /**
     * Test that XSS attack vectors are sanitized
     * @param string $input
     * @param string $expected
     */
    public function testXssAttackVectorsAreSanitized($input, $expected)
    {
        $result = simple_jwt_login_sanitize_css_value($input);
        $this->assertSame($expected, $result);
        // Ensure no dangerous characters remain
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString(';', $result);
        $this->assertStringNotContainsString('{', $result);
        $this->assertStringNotContainsString('}', $result);
    }

    /**
     * @return array[]
     */
    public static function xssAttackVectorsProvider()
    {
        return [
            'script injection' => [
                'red;} </style><script>alert("XSS")</script><style>x{',
                'red alertXSSx'
            ],
            'closing style tag' => [
                '</style><script>alert(1)</script>',
                'alert1'
            ],
            'quote breaking' => [
                'red"; onclick="alert(1)"',
                'red onclick=alert1'
            ],
            'single quote breaking' => [
                "red'; onclick='alert(1)'",
                'red onclick=alert1'
            ],
            'curly brace injection' => [
                'red} body{background:url(javascript:alert(1))',
                'red bodybackground:urljavascript:alert1'
            ],
            'semicolon injection' => [
                'red; background-image: url(evil.com)',
                'red background-image: urlevil.com'
            ],
            'backslash escape attempt' => [
                'red\\"; alert(1); //',
                'red alert1 '
            ],
            'html tags' => [
                '<img src=x onerror=alert(1)>',
                '' // HTML tags are completely stripped by wp_strip_all_tags
            ],
            'backtick injection' => [
                'red`; alert`1`',
                'red alert1'
            ],
        ];
    }

    #[DataProvider('htmlTagRemovalProvider')]
    /**
     * Test that HTML tags are stripped
     * @param string $input
     * @param string $expected
     */
    public function testHtmlTagsAreStripped($input, $expected)
    {
        $this->assertSame($expected, simple_jwt_login_sanitize_css_value($input));
    }

    /**
     * @return array[]
     */
    public static function htmlTagRemovalProvider()
    {
        return [
            'simple tag' => ['<b>red</b>', 'red'],
            'script tag' => ['<script>alert(1)</script>', 'alert1'],
            'style tag' => ['<style>body{}</style>', 'body'],
            'img tag' => ['<img src="x">', ''], // self-closing tag with no content
            'nested tags' => ['<div><span>red</span></div>', 'red'],
        ];
    }

    /**
     * Test that values are truncated to 100 characters
     */
    public function testLengthIsTruncated()
    {
        $longValue = str_repeat('a', 150);
        $result = simple_jwt_login_sanitize_css_value($longValue);
        $this->assertSame(100, strlen($result));
        $this->assertSame(str_repeat('a', 100), $result);
    }

    /**
     * Test that the function handles null-like values gracefully
     */
    public function testHandlesEmptyInput()
    {
        $this->assertSame('', simple_jwt_login_sanitize_css_value(''));
        $this->assertSame('0', simple_jwt_login_sanitize_css_value('0'));
    }
}
