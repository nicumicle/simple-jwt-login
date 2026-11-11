<?php

namespace SimpleJWTLogin\Modules\Abilities;

use SimpleJWTLogin\Modules\Settings\SettingsFactory;

/**
 * Lists the top-level settings section keys, derived from SettingsFactory so a
 * new settings class is picked up automatically.
 */
class SettingsSections
{
    /**
     * @return string[]
     */
    public static function all()
    {
        $sections = [];
        foreach ((new SettingsFactory())->getAll() as $parser) {
            $sections[] = (string) key((array) $parser->getSettings());
        }
        sort($sections);

        return $sections;
    }

    /**
     * Sections where at least the section itself is not read-only. Sections
     * that only need an AI permission are kept: the list is built once per
     * process, and a call without the permission gets an error naming it.
     *
     * @param AbilitySettingsPolicy $policy
     * @return string[]
     */
    public static function writable(AbilitySettingsPolicy $policy)
    {
        return array_values(array_filter(self::all(), function ($section) use ($policy) {
            return $policy->isWritable($section) || $policy->missingPermission($section) !== '';
        }));
    }
}
