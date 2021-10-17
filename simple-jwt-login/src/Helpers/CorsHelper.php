<?php

namespace SimpleJWTLogin\Helpers;

class CorsHelper
{
    /**
     * @param string $headerName
     * @param string $value
     */
    public function addHeader($headerName, $value)
    {
        header($headerName . ": " . $value);
    }
}
