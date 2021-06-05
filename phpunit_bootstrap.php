<?php
define('ABSPATH', 'PHPunit');
error_reporting(E_ALL);

if (! function_exists('__')) {
    function __($text, $domain)
	{
        if ($domain === null) {
            throw new Exception('Missing domain.');
        }
        return $text;
    }
}

require_once "simple-jwt-login/autoload.php";
require_once "vendor/autoload.php";
