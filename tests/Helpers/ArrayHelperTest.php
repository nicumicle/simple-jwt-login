<?php

namespace SimpleJwtLoginTests\Helpers;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ArrayHelper;

class ArrayHelperTest extends TestCase
{
    /**
     * @dataProvider convertStringToArrayProvider
     * @param string $string
     * @param string[] $expectedResult
     * @return void
     */
    public function testConvertStringToArray($string, $expectedResult)
    {
        $result = ArrayHelper::convertStringToArray($string);

        $this->assertEquals(
            $expectedResult,
            $result
        );
    }

    /**
     * @return array
     */
    public static function convertStringToArrayProvider()
    {
        return [
            'empty_string' => [
                'string' => '',
                'result' => [
                    0 => '',
                ],
            ],
            'string_without_spaces' => [
                'string' => 'a,b,c',
                'result' => [
                    'a',
                    'b',
                    'c',
                ],
            ],
            'strings_with_spaces' => [
                'string' => 'a, b, c',
                'result' => [
                    'a',
                    'b',
                    'c',
                ],
            ],
        ];
    }
}
