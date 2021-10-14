<?php

namespace SimpleJWTLogin\Helpers;

use Exception;

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

    /**
     * @param string $view
     * @return string
     * @throws Exception
     */
    public static function path($view)
    {
        $regex = '/(?:[\.\/])*((?:[a-zA-Z0-9_\-.]+)\.php)/mi';
        preg_match($regex, $view, $matches);

        if (isset($matches[1])) {
            return self::text($matches[1]);
        }
        throw new Exception(__('Invalid path provided or file does not exists', 'simple-jwt-login'));
    }
}
