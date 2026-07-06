<?php

namespace SimpleJwtLoginTests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Plugin\Shortcodes;

class SanitizeCssValueTest extends TestCase
{
    #[DataProvider('validCssValuesProvider')]
    /**
     * @param string $input
     * @param string $expected
     */
    public function testValidCssValues($input, $expected)
    {
        $this->assertSame($expected, Shortcodes::sanitizeCssValue($input));
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
     * @param string $input
     * @param string $expected
     */
    public function testXssAttackVectorsAreSanitized($input, $expected)
    {
        $result = Shortcodes::sanitizeCssValue($input);
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
     * @param string $input
     * @param string $expected
     */
    public function testHtmlTagsAreStripped($input, $expected)
    {
        $this->assertSame($expected, Shortcodes::sanitizeCssValue($input));
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
        $result = Shortcodes::sanitizeCssValue($longValue);
        $this->assertSame(100, strlen($result));
        $this->assertSame(str_repeat('a', 100), $result);
    }

    /**
     * Test that the function handles null-like values gracefully
     */
    public function testHandlesEmptyInput()
    {
        $this->assertSame('', Shortcodes::sanitizeCssValue(''));
        $this->assertSame('0', Shortcodes::sanitizeCssValue('0'));
    }

    // --- sanitizeColor ---

    #[DataProvider('validColorsProvider')]
    /**
     * @param string $input
     * @param string $expected
     */
    public function testSanitizeColorAcceptsValidValues($input, $expected)
    {
        $this->assertSame($expected, Shortcodes::sanitizeColor($input));
    }

    /**
     * @return array[]
     */
    public static function validColorsProvider()
    {
        return [
            'hex short'          => ['#fff', '#fff'],
            'hex long'           => ['#1a2b3c', '#1a2b3c'],
            'hex uppercase'      => ['#FFFFFF', '#FFFFFF'],
            'named black'        => ['black', 'black'],
            'named red'          => ['red', 'red'],
            'named transparent'  => ['transparent', 'transparent'],
            'named with spaces'  => ['  white  ', 'white'],
        ];
    }

    #[DataProvider('invalidColorsProvider')]
    /**
     * @param string $input
     */
    public function testSanitizeColorRejectsInvalidValues($input)
    {
        $this->assertSame('', Shortcodes::sanitizeColor($input));
    }

    /**
     * @return array[]
     */
    public static function invalidColorsProvider()
    {
        return [
            'rgb function'         => ['rgb(255,0,0)'],
            'css variable'         => ['var(--primary)'],
            'expression IE hack'   => ['expression(alert(1))'],
            'property injection'   => ['red; color: blue'],
            'newline injection'    => ["red\ncolor:blue"],
            'comment injection'    => ['red /* comment */'],
            'unknown named color'  => ['chartreuse'],
            'empty string'         => [''],
        ];
    }

    // --- sanitizeDimension ---

    #[DataProvider('validDimensionsProvider')]
    /**
     * @param string $input
     * @param string $expected
     */
    public function testSanitizeDimensionAcceptsValidValues($input, $expected)
    {
        $this->assertSame($expected, Shortcodes::sanitizeDimension($input));
    }

    /**
     * @return array[]
     */
    public static function validDimensionsProvider()
    {
        return [
            'pixels'      => ['30px', '30px'],
            'em'          => ['1.5em', '1.5em'],
            'rem'         => ['2rem', '2rem'],
            'percentage'  => ['100%', '100%'],
            'vh'          => ['50vh', '50vh'],
            'vw'          => ['50vw', '50vw'],
            'with spaces' => ['  20px  ', '20px'],
        ];
    }

    #[DataProvider('invalidDimensionsProvider')]
    /**
     * @param string $input
     */
    public function testSanitizeDimensionRejectsInvalidValues($input)
    {
        $this->assertSame('', Shortcodes::sanitizeDimension($input));
    }

    /**
     * @return array[]
     */
    public static function invalidDimensionsProvider()
    {
        return [
            'no unit'          => ['30'],
            'unsupported unit' => ['30pt'],
            'negative value'   => ['-10px'],
            'calc expression'  => ['calc(100% - 20px)'],
            'extra content'    => ['30px solid'],
            'empty string'     => [''],
        ];
    }

    // --- sanitizeBorder ---

    #[DataProvider('validBordersProvider')]
    /**
     * @param string $input
     * @param string $expected
     */
    public function testSanitizeBorderAcceptsValidValues($input, $expected)
    {
        $this->assertSame($expected, Shortcodes::sanitizeBorder($input));
    }

    /**
     * @return array[]
     */
    public static function validBordersProvider()
    {
        return [
            'px solid hex'    => ['1px solid #ccc', '1px solid #ccc'],
            'em dashed named' => ['2em dashed red', '2em dashed red'],
            'keyword width'   => ['thin solid black', 'thin solid black'],
            'medium dotted'   => ['medium dotted #000000', 'medium dotted #000000'],
            'with spaces'     => ['  1px solid #fff  ', '1px solid #fff'],
        ];
    }

    #[DataProvider('invalidBordersProvider')]
    /**
     * @param string $input
     */
    public function testSanitizeBorderFallsBackToDefaultForInvalidValues($input)
    {
        $this->assertSame('1px solid #ccc', Shortcodes::sanitizeBorder($input));
    }

    /**
     * @return array[]
     */
    public static function invalidBordersProvider()
    {
        return [
            'color only'             => ['red'],
            'width only'             => ['1px'],
            'property injection'     => ['1px solid red; color: blue'],
            'block injection'        => ['1px solid red} body{'],
            'expression hack'        => ['1px solid expression(alert(1))'],
            'missing color'          => ['1px solid'],
            'empty string'           => [''],
        ];
    }
}
