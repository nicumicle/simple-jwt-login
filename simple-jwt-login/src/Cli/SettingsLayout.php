<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Modules\Settings\Migrations\SettingsMigrationService;

/**
 * Knows how Simple JWT Login stores its settings: in sections (authorization.enabled).
 *
 * Old flat keys (allow_authentication) and old export files are converted with the settings migration.
 */
class SettingsLayout
{
    const VERSION_KEY = '_schema_version';

    /**
     * Sections that flat (old) settings do not have.
     *
     * @var string[]
     */
    private static $sections = array(
        'general',
        'login',
        'register',
        'authorization',
        'delete_user',
        'hooks',
        'reset_password',
        'protect_endpoint',
        'integrations',
    );

    /**
     * Settings as the plugin reads them.
     *
     * @param array $settings
     * @return array
     */
    public function normalize(array $settings)
    {
        return SettingsMigrationService::migrate($settings);
    }

    /**
     * @param string $key
     * @return void
     * @throws Exception When the key is an old flat key.
     */
    public function assertKey($key)
    {
        $parts = explode('.', $key, 2);
        $head = $parts[0];

        $path = $this->findNewPath($head);
        if ($path === null || $path[0] === $head) {
            return;
        }

        $parts[0] = implode('.', $path);
        throw new Exception(sprintf(
            /* translators: 1: old setting key, 2: new setting key */
            esc_html__('Setting %1$s is now %2$s.', 'simple-jwt-login'),
            esc_html($key),
            esc_html(implode('.', $parts))
        ));
    }

    /**
     * @param string $key
     * @param array  $settings Settings that will be saved.
     * @return void
     * @throws Exception When saving the key would have no effect or break the settings.
     */
    public function assertWritable($key, array $settings)
    {
        $this->assertKey($key);

        $parts = explode('.', $key, 2);
        $section = isset($settings[$parts[0]]) ? $settings[$parts[0]] : null;
        if (!is_array($section) || isset($section[0])) {
            throw new Exception(sprintf(
                /* translators: %s is the setting key */
                esc_html__('%s is not a settings section.', 'simple-jwt-login'),
                esc_html($parts[0])
            ));
        }
    }

    /**
     * @param array $imported
     * @param bool  $merge
     * @return array Settings in the current layout.
     * @throws Exception When the file can not be imported.
     */
    public function prepareImport(array $imported, $merge)
    {
        $convert = !$merge && !isset($imported[self::VERSION_KEY]);
        if ($convert && array_intersect(self::$sections, array_keys($imported)) !== array()) {
            // Migrating would silently drop the sections.
            throw new Exception(sprintf(
                /* translators: %s is the setting key */
                esc_html__('The file has sectioned settings, but no %s.', 'simple-jwt-login'),
                self::VERSION_KEY
            ));
        }

        $settings = $convert ? $this->normalize($imported) : $imported;
        foreach (array_keys($settings) as $key) {
            if ($key === self::VERSION_KEY) {
                continue;
            }
            $this->assertWritable((string) $key, $settings);
        }

        return $settings;
    }

    /**
     * Where the plugin migration moves an old flat key.
     *
     * @param string $key
     * @return string[]|null
     */
    private function findNewPath($key)
    {
        // An array, because the migration skips some keys when they are not arrays.
        $probe = array('simple-jwt-login-cli' => true);

        return $this->findPath($this->normalize(array($key => $probe)), $probe);
    }

    /**
     * @param array $settings
     * @param array $probe
     * @return string[]|null
     */
    private function findPath(array $settings, array $probe)
    {
        foreach ($settings as $key => $value) {
            if ($value === $probe) {
                return array((string) $key);
            }
            $path = is_array($value) ? $this->findPath($value, $probe) : null;
            if ($path !== null) {
                return array_merge(array((string) $key), $path);
            }
        }

        return null;
    }
}
