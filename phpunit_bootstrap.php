<?php
define('ABSPATH', 'PHPunit');
error_reporting(E_ALL);

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain ) {
        return $text;
    }
}

require_once "simple-jwt-login/src/autoload.php";
require_once "vendor/autoload.php";
