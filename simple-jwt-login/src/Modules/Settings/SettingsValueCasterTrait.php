<?php

namespace SimpleJWTLogin\Modules\Settings;

use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

trait SettingsValueCasterTrait
{
    /**
     * @param mixed $value
     * @param int $type
     * @param WordPressDataInterface $wpData
     * @return bool|int|string
     */
    protected function castValue($value, $type, WordPressDataInterface $wpData)
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
