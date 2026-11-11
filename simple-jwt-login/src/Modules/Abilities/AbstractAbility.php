<?php

namespace SimpleJWTLogin\Modules\Abilities;

use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

abstract class AbstractAbility implements AbilityInterface
{
    const PREFIX           = 'simple-jwt-login/';
    const CAPABILITY_ADMIN = 'manage_options';
    const CAPABILITY_USER  = 'read';

    /**
     * @var SimpleJWTLoginSettings
     */
    protected $jwtSettings;

    /**
     * @param SimpleJWTLoginSettings $jwtSettings
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings)
    {
        $this->jwtSettings = $jwtSettings;
    }

    /**
     * Short ability slug, without the plugin prefix.
     *
     * @return string
     */
    abstract protected function getSlug();

    /**
     * @return string
     */
    public function getName()
    {
        return self::PREFIX . $this->getSlug();
    }

    /**
     * No extra AI permission needed by default.
     *
     * @return string
     */
    public function getRequiredPermission()
    {
        return '';
    }

    /**
     * Administrators only by default.
     *
     * @return string
     */
    public function getRequiredCapability()
    {
        return self::CAPABILITY_ADMIN;
    }

    /**
     * @return bool
     */
    protected function isAdministrator()
    {
        return (bool) $this->jwtSettings->getWordPressData()->currentUserCan(self::CAPABILITY_ADMIN);
    }

    /**
     * The administrator's AI permissions, read at call time (settings are
     * reloaded before every execution).
     *
     * @return AiAbilitiesSettings
     */
    protected function aiSettings()
    {
        return $this->jwtSettings->getIntegrationsSettings()->aiAbilities();
    }

    /**
     * Settings policy that applies the current AI permissions.
     *
     * @return AbilitySettingsPolicy
     */
    protected function permissionPolicy()
    {
        return (new AbilitySettingsPolicy())->withPermissions($this->aiSettings());
    }

    /**
     * @param array  $input
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function inputValue(array $input, $key, $default = null)
    {
        return array_key_exists($key, $input) ? $input[$key] : $default;
    }

    /**
     * Object input schema. "default" lets the ability be called without input.
     *
     * @param array    $properties
     * @param string[] $required
     * @return array
     */
    protected function objectSchema(array $properties, array $required = [])
    {
        $schema = [
            'type'                 => 'object',
            'properties'           => $properties,
            'additionalProperties' => false,
        ];
        if (!empty($required)) {
            $schema['required'] = $required;
            return $schema;
        }
        $schema['default'] = [];

        return $schema;
    }

    /**
     * @param bool $readOnly
     * @param bool $destructive
     * @param bool $idempotent
     * @return array
     */
    protected function annotations($readOnly, $destructive, $idempotent)
    {
        return [
            'readonly'    => $readOnly,
            'destructive' => $destructive,
            'idempotent'  => $idempotent,
        ];
    }
}
