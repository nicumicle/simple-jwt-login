<?php

namespace SimpleJWTLogin\Helpers;

use SimpleJWTLogin\ErrorCodes;

class StatusCodeHelper
{
    /**
     * @param \Exception $exception
     * @return int
     */
    public static function getStatusCodeFromExeption($exception, $defaultStatusCode)
    {
        $unauthorizedCode =  array(
            ErrorCodes::ERR_REVOKED_TOKEN,
            ErrorCodes::ERR_TOKEN_EXPIRED,
            ErrorCodes::ERR_TOKEN_IAT,
            ErrorCodes::ERR_TOKEN_NBF,
        );

        if (in_array($exception->getCode(), $unauthorizedCode)) {
            return 401;
        }

        return $defaultStatusCode;
    }
}
