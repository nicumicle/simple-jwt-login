<?php

namespace SimpleJWTLogin\Helpers;

class ArrayHelper
{
    /**
     * @param string $string
     * @return array|string[]
     */
    public static function convertStringToArray($string)
    {
        return array_map(function ($value) {
            return trim($value);
        }, explode(',', $string));
    }
}
