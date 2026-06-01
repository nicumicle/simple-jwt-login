<?php

namespace SimpleJWTLogin\Modules\Settings\Migrations;

use Exception;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class SettingsMigrationService
{
    /**
     * Run all pending migrations on the given settings array.
     *
     * @param array $settings
     * @return array
     * @throws Exception
     */
    public static function migrate($settings)
    {
        if (!is_array($settings)) {
            $settings = [];
        }

        $version = isset($settings['_schema_version'])
            ? (int) $settings['_schema_version']
            : 1;

        $target = SimpleJWTLoginSettings::SCHEMA_VERSION;

        while ($version < $target) {
            $migration = self::getMigration($version);
            $settings  = $migration->migrate($settings);
            $version   = $migration->getTargetVersion();
        }

        return $settings;
    }

    /**
     * @param int $fromVersion
     * @return MigrationInterface
     * @throws Exception
     */
    private static function getMigration($fromVersion)
    {
        if ($fromVersion === 1) {
            return new V1ToV2Migration();
        }

        throw new Exception(
            esc_html(sprintf('No settings migration found from version %d.', $fromVersion))
        );
    }
}
