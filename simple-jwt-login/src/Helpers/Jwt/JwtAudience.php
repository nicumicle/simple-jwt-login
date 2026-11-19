<?php

namespace SimpleJWTLogin\Helpers\Jwt;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;

/**
 * Optional "aud" binding: the plugin's own JWTs carry the configured audience, and a JWT is only
 * accepted when its "aud" (a string or a list, RFC 7519) contains the expected audience - the one
 * of the matched JWT Rule, or the plugin's own one.
 */
class JwtAudience
{
    const CLAIM = 'aud';

    /**
     * @param array $payload
     * @param AuthenticationSettings $authSettings
     * @return array
     */
    public static function addToPayload(array $payload, AuthenticationSettings $authSettings)
    {
        $audience = $authSettings->getJwtAudience();
        if ($audience !== '') {
            $payload[self::CLAIM] = $audience;
        }

        return $payload;
    }

    /**
     * @param AuthenticationSettings $authSettings
     * @param array|null $ruleConfig The JWT Rule the token matched, if any.
     * @return string
     */
    public static function expected(AuthenticationSettings $authSettings, $ruleConfig)
    {
        if ($ruleConfig !== null) {
            return isset($ruleConfig['audience']) ? trim((string) $ruleConfig['audience']) : '';
        }

        return $authSettings->getJwtAudience();
    }

    /**
     * @param array $payload
     * @param string $expectedAudience
     * @return bool
     */
    public static function isAccepted(array $payload, $expectedAudience)
    {
        if ($expectedAudience === '') {
            return true;
        }
        $audience = isset($payload[self::CLAIM]) ? $payload[self::CLAIM] : null;
        if (is_array($audience)) {
            return in_array($expectedAudience, $audience, true);
        }

        return $audience === $expectedAudience;
    }

    /**
     * @param array $payload
     * @param string $expectedAudience
     * @return void
     * @throws Exception
     */
    public static function assertAccepted(array $payload, $expectedAudience)
    {
        if (!self::isAccepted($payload, $expectedAudience)) {
            throw new Exception(
                esc_html(__('The JWT was not issued for this audience (aud).', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_JWT_AUDIENCE_MISMATCH)
            );
        }
    }
}
