<?php

namespace SimpleJWTLogin\Modules\Abilities;

/**
 * Reads a dot-notation path (e.g. "integrations.oauth.google.client_id")
 * from a settings array.
 */
class SettingsPath
{
    /**
     * @param array  $settings
     * @param string $path
     * @return bool
     */
    public static function exists(array $settings, $path)
    {
        $result = self::walk($settings, $path);

        return $result[0];
    }

    /**
     * @param array  $settings
     * @param string $path
     * @return mixed Null when the path does not exist.
     */
    public static function get(array $settings, $path)
    {
        $result = self::walk($settings, $path);

        return $result[1];
    }

    /**
     * @param array  $settings
     * @param string $path
     * @return array{0: bool, 1: mixed} [found, value]
     */
    private static function walk(array $settings, $path)
    {
        $node = $settings;
        foreach (explode('.', (string) $path) as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return [false, null];
            }
            $node = $node[$segment];
        }

        return [true, $node];
    }
}
