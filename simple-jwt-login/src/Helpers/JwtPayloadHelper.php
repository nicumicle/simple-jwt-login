<?php

namespace SimpleJWTLogin\Helpers;

class JwtPayloadHelper
{
    /**
     * Decodes a JWT's payload segment without verifying its signature.
     *
     * @param string $jwt
     * @return array|null
     */
    public static function decode($jwt)
    {
        $jwtParts = explode('.', $jwt);
        if (!isset($jwtParts[1])) {
            return null;
        }
        $segment = $jwtParts[1];
        $remainder = strlen($segment) % 4;
        if ($remainder !== 0) {
            $segment .= str_repeat('=', 4 - $remainder);
        }
        return json_decode(base64_decode(strtr($segment, '-_', '+/')), true);
    }
}
