<?php

namespace SimpleJWTLogin\Helpers;

class Sanitizer
{
    /**
     * @param string $string
     * @return string
     */
    public static function html($string)
    {
        return esc_html($string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function attribute($string)
    {
        return esc_attr($string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function url($string)
    {
        return esc_url($string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function text($string)
    {
        return self::html($string);
    }
}
