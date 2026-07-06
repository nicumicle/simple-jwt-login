<?php

namespace SimpleJWTLogin\Routes;

class SessionService
{
    /**
     * @return array
     */
    public static function init()
    {
        switch (session_status()) {
            case PHP_SESSION_DISABLED:
                return [];
            case PHP_SESSION_NONE:
                if (headers_sent()) {
                    return [];
                }
                session_start();
                return $_SESSION;
            case PHP_SESSION_ACTIVE:
                return $_SESSION;
            default:
                return [];
        }
    }
}
