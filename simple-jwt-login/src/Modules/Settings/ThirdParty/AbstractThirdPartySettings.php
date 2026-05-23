<?php

namespace SimpleJWTLogin\Modules\Settings\ThirdParty;

use SimpleJWTLogin\Modules\Settings\BaseSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

/**
 * Base class for every 3rd-party integration settings block.
 *
 * Subclasses declare their own data via:
 *  - getExtraFields()  - additional fields beyond the common enabled toggle
 *
 * Registering a new 3rd-party integration requires only:
 *  1. Create a subclass that fills in getExtraFields() as needed.
 *  2. Add provider-specific getters as needed.
 *  3. Register the class in ApplicationsSettings::buildThirdPartyApps().
 */
abstract class AbstractThirdPartySettings
{
    /** @var array<string, mixed> */
    private $data = [];

    // =========================================================================
    // Abstract identity - subclasses must implement these
    // =========================================================================

    /**
     * Settings group key, also used as the POST field prefix (e.g. "wpgraphql").
     *
     * @return string
     */
    abstract public function getGroup();

    // =========================================================================
    // Hook methods - override in subclasses to customise behaviour
    // =========================================================================

    /**
     * Additional fields specific to this integration, beyond the common enabled toggle.
     * Format: [['field_name', BaseSettings::SETTINGS_TYPE_*], ...]
     *
     * @return array<array{0: string, 1: int}>
     */
    protected function getExtraFields()
    {
        return [];
    }

    // =========================================================================
    // Lifecycle - called by ApplicationsSettings
    // =========================================================================

    /**
     * Read the integration's POST slice and return a sanitised settings array.
     *
     * @param array $post Full POST array.
     * @param WordPressDataInterface $wpData
     * @return array<string, mixed>
     */
    public function processPost($post, WordPressDataInterface $wpData)
    {
        $group  = $this->getGroup();
        $result = [];

        foreach ($this->allFields() as $field) {
            list($name, $type) = $field;
            $raw           = isset($post[$group][$name]) ? $post[$group][$name] : null;
            $result[$name] = $this->castValue($raw, $type, $wpData);
        }

        return $result;
    }

    /**
     * Hydrate this object with the stored settings slice for this integration.
     * Returns $this so it can be chained.
     *
     * @param array<string, mixed> $settings
     * @return $this
     */
    public function withSettings($settings)
    {
        $this->data = $settings;

        return $this;
    }

    // =========================================================================
    // Common getters
    // =========================================================================

    /** @return bool */
    public function isEnabled()
    {
        return !empty($this->data['enabled']);
    }

    /**
     * Generic string getter for extra fields.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function get($key, $default = '')
    {
        return (string)(isset($this->data[$key]) ? $this->data[$key] : $default);
    }

    /**
     * Generic boolean getter for extra fields.
     *
     * @param string $key
     * @return bool
     */
    public function isFieldEnabled($key)
    {
        return !empty($this->data[$key]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * @return array<array{0: string, 1: int}>
     */
    private function allFields()
    {
        return array_merge($this->commonFields(), $this->getExtraFields());
    }

    /**
     * @return array<array{0: string, 1: int}>
     */
    private function commonFields()
    {
        return [
            ['enabled', BaseSettings::SETTINGS_TYPE_BOL],
        ];
    }

    /**
     * @param mixed $value
     * @param int $type
     * @param WordPressDataInterface $wpData
     * @return bool|int|string
     */
    private function castValue($value, $type, WordPressDataInterface $wpData)
    {
        if ($value === null) {
            switch ($type) {
                case BaseSettings::SETTINGS_TYPE_BOL:
                    return false;
                case BaseSettings::SETTINGS_TYPE_INT:
                    return 0;
                default:
                    return '';
            }
        }

        switch ($type) {
            case BaseSettings::SETTINGS_TYPE_INT:
                return (int) $value;
            case BaseSettings::SETTINGS_TYPE_BOL:
                return (bool) $value;
            case BaseSettings::SETTINGS_TYPE_STRING:
                return $wpData->sanitizeTextField($value);
            default:
                return $wpData->sanitizeTextField($value);
        }
    }
}
