<?php

namespace SimpleJWTLogin\Helpers\Jwt;

use SimpleJWTLogin\Modules\Settings\LoginSettings;

/**
 * How a JWT maps to a WordPress user: with the settings of the JWT Rule it matched,
 * else with the global login settings.
 */
class JwtLoginBy
{
    /**
     * @param LoginSettings $loginSettings
     * @param array|null    $ruleConfig the matched JWT Rule, null when none matched
     *
     * @return array{login_by: int, parameter: string}
     */
    public static function resolve(LoginSettings $loginSettings, $ruleConfig)
    {
        if ($ruleConfig === null) {
            return [
                'login_by'  => (int) $loginSettings->getJWTLoginBy(),
                'parameter' => (string) $loginSettings->getJwtLoginByParameter(),
            ];
        }

        return [
            'login_by'  => isset($ruleConfig['login_by'])
                ? (int) $ruleConfig['login_by']
                : LoginSettings::JWT_LOGIN_BY_EMAIL,
            'parameter' => isset($ruleConfig['login_by_parameter']) ? (string) $ruleConfig['login_by_parameter'] : '',
        ];
    }
}
