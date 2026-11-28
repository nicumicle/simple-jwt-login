<?php

namespace SimpleJWTLogin\Helpers\Jwt;

use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\JwtRulesSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\BaseService;

/**
 * Non-breaking signing key rotation: the replaced key stays in a JWT Rule (`iat` less than the
 * rotation time), so tokens issued before the rotation keep working until they expire.
 * The rule is removed once the JWT lifetime has passed (daily cleanup, next rotation).
 */
class KeyRotation
{
    /**
     * Settings with a rule that keeps the current signing key for the tokens issued before $now.
     * Nothing is added when there is no key in the settings (for example keys in wp-config.php).
     * An empty refresh token key gets the current HS key, so issued refresh tokens keep working.
     *
     * @param array                  $settings    Stored settings.
     * @param SimpleJWTLoginSettings $jwtSettings The same settings, for the login-by and audience getters.
     * @param int                    $now
     * @return array
     */
    public static function keepCurrentKey(array $settings, SimpleJWTLoginSettings $jwtSettings, $now)
    {
        $general = isset($settings['general']) ? $settings['general'] : array();
        $source = isset($general['decryption_source']) ? (string) $general['decryption_source'] : '';
        if ($source === GeneralSettings::DECRYPTION_SOURCE_CODE) {
            return $settings;
        }
        // Refresh tokens are hashed with the signing key while they have no key of their own: keep it for them.
        if (empty($settings['authorization']['refresh_token_key']) && !empty($general['decryption_key'])) {
            $settings['authorization']['refresh_token_key'] = $general['decryption_key'];
        }
        $algorithm = !empty($general['jwt_algorithm']) ? (string) $general['jwt_algorithm'] : 'HS256';
        $keyFields = strpos($algorithm, 'RS') === 0
            ? array('decryption_key_public', 'decryption_key_private')
            : array('decryption_key', 'decryption_key_base64');
        if (empty($general[$keyFields[0]])) {
            return $settings;
        }

        $loginSettings = $jwtSettings->getLoginSettings();
        $rule = array(
            'condition_type'     => 'payload',
            'condition_key'      => JwtRulesSettings::ROTATION_CLAIM,
            'condition_operator' => JwtRulesSettings::OPERATOR_LESS_THAN,
            'condition_value'    => (string) (int) $now,
            'algorithm'          => $algorithm,
            // A matching rule replaces the global login-by and audience: keep them.
            'login_by'           => (int) $loginSettings->getJWTLoginBy(),
            'login_by_parameter' => (string) $loginSettings->getJwtLoginByParameter(),
            'audience'           => $jwtSettings->getAuthenticationSettings()->getJwtAudience(),
        );
        foreach ($keyFields as $field) {
            // Same storage as the general key: RS keys are base64 in both places.
            $rule[$field] = isset($general[$field]) ? $general[$field] : '';
        }
        if (isset($rule['decryption_key_base64'])) {
            $rule['decryption_key_base64'] = !empty($rule['decryption_key_base64']);
        }

        $rules = isset($settings['jwt_rules']['rules']) ? (array) $settings['jwt_rules']['rules'] : array();
        $rules[] = $rule;
        $settings['jwt_rules']['rules'] = $rules;

        return $settings;
    }

    /**
     * Settings without the rotation rules whose tokens have all expired.
     *
     * @param array $settings
     * @param int   $graceSeconds From graceSeconds().
     * @param int   $now
     * @return array
     */
    public static function removeExpired(array $settings, $graceSeconds, $now)
    {
        return self::filterRules($settings, function ($rule) use ($graceSeconds, $now) {
            return (int) $rule['condition_value'] + $graceSeconds < $now;
        });
    }

    /**
     * Settings without any rotation rule: every key replaced before stops working.
     *
     * @param array $settings
     * @return array
     */
    public static function removeAll(array $settings)
    {
        return self::filterRules($settings, function () {
            return true;
        });
    }

    /**
     * How long a rotated key is kept: the JWT lifetime plus the validation leeway.
     *
     * @param SimpleJWTLoginSettings $jwtSettings
     * @return int Seconds.
     */
    public static function graceSeconds(SimpleJWTLoginSettings $jwtSettings)
    {
        return max(0, (int) $jwtSettings->getAuthenticationSettings()->getAuthJwtTtl()) * 60 + BaseService::JWT_LEEWAY;
    }

    /**
     * @param array    $settings
     * @param callable $remove Takes a rotation rule, returns true to remove it.
     * @return array
     */
    private static function filterRules(array $settings, $remove)
    {
        if (empty($settings['jwt_rules']['rules']) || !is_array($settings['jwt_rules']['rules'])) {
            return $settings;
        }
        $kept = array();
        foreach ($settings['jwt_rules']['rules'] as $rule) {
            if (is_array($rule) && JwtRulesSettings::isKeyRotationRule($rule) && call_user_func($remove, $rule)) {
                continue;
            }
            $kept[] = $rule;
        }
        $settings['jwt_rules']['rules'] = $kept;

        return $settings;
    }
}
