<?php

namespace SimpleJwtLoginTests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ArrayHelper;

class ArrayHelperTest extends TestCase
{
    #[DataProvider('convertStringToArrayProvider')]
    /**
     * @param string $string
     * @param string[] $result
     * @return void
     */
    public function testConvertStringToArray($string, $result)
    {
        $data = ArrayHelper::convertStringToArray($string);

        $this->assertEquals(
            $result,
            $data
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
