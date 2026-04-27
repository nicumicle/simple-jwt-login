<?php

namespace SimpleJwtLoginTests\WP\Autologin;

use SimpleJwtLoginTests\WP\WPTestCase;

abstract class AutologinTestCase extends WPTestCase
{
    protected const ROUTE = '/simple-jwt-login/v1/autologin';
}
