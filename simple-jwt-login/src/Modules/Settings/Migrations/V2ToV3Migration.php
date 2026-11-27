<?php

namespace SimpleJWTLogin\Modules\Settings\Migrations;

/**
 * Adds the new "jti" claim (enabled by default) to the saved JWT payload list.
 *
 * The list holds the enabled claims, so a claim added later would stay off for every
 * existing install. A payload that was never saved needs nothing: jti is on by default.
 */
class V2ToV3Migration implements MigrationInterface
{
    public function getSourceVersion()
    {
        return 2;
    }

    public function getTargetVersion()
    {
        return 3;
    }

    public function migrate($settings)
    {
        if (!is_array($settings)) {
            $settings = [];
        }

        $result = $settings;
        $result['_schema_version'] = 3;

        if (!isset($result['authorization']['jwt_payload']) || !is_array($result['authorization']['jwt_payload'])) {
            return $result;
        }

        if (!in_array('jti', $result['authorization']['jwt_payload'], true)) {
            $result['authorization']['jwt_payload'][] = 'jti';
        }

        return $result;
    }
}
