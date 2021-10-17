<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class CorsSettings extends BaseSettings implements SettingsInterface
{
    const DEFAULT_HEADER_PARAMETER = '*';
    const DEFAULT_METHODS = 'GET, POST, PUT, DELETE, OPTIONS, HEAD';

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            'cors',
            'enabled',
            'cors',
            'enabled',
            BaseSettings::SETTINGS_TYPE_INT
        );

        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_origin_enabled',
            'cors',
            'allow_origin_enabled',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_origin',
            'cors',
            'allow_origin',
            BaseSettings::SETTINGS_TYPE_STRING
        );

        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_methods_enabled',
            'cors',
            'allow_methods_enabled',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_methods',
            'cors',
            'allow_methods',
            BaseSettings::SETTINGS_TYPE_STRING
        );

        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_headers_enabled',
            'cors',
            'allow_headers_enabled',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_headers',
            'cors',
            'allow_headers',
            BaseSettings::SETTINGS_TYPE_STRING
        );
    }

    public function validateSettings()
    {
        if (!empty($this->settings['cors']['enabled'])
            && (
                empty($this->settings['cors']['allow_origin_enabled'])
                && empty($this->settings['cors']['allow_methods_enabled'])
                && empty($this->settings['cors']['allow_headers_enabled'])
            )
        ) {
            throw new Exception(
                __(
                    'Cors is enabled but no option is checked. Please check at least one option.',
                    'simple-jwt-login'
                ),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_CORS,
                    SettingsErrors::ERR_CORS_NO_OPTION
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isCorsEnabled()
    {
        return isset($this->settings['cors']) && ! empty($this->settings['cors']['enabled']);
    }

    /**
     * @return bool
     */
    public function isAllowOriginEnabled()
    {
        return isset($this->settings['cors'])
            && isset($this->settings['cors']['allow_origin_enabled'])
            && filter_var($this->settings['cors']['allow_origin_enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return string
     */
    public function getAllowOrigin()
    {
        return isset($this->settings['cors']) && isset($this->settings['cors']['allow_origin'])
            ? $this->settings['cors']['allow_origin']
            : self::DEFAULT_HEADER_PARAMETER;
    }

    /**
     * @return bool
     */
    public function isAllowHeadersEnabled()
    {
        return isset($this->settings['cors'])
            && isset($this->settings['cors']['allow_headers_enabled'])
            && filter_var($this->settings['cors']['allow_headers_enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return string
     */
    public function getAllowHeaders()
    {
        return isset($this->settings['cors']) && isset($this->settings['cors']['allow_headers'])
            ? $this->settings['cors']['allow_headers']
            : self::DEFAULT_HEADER_PARAMETER;
    }

    /**
     * @return bool
     */
    public function isAllowMethodsEnabled()
    {
        return isset($this->settings['cors'])
            && isset($this->settings['cors']['allow_methods_enabled'])
            && filter_var($this->settings['cors']['allow_methods_enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return string
     */
    public function getAllowMethods()
    {
        return isset($this->settings['cors']) && isset($this->settings['cors']['allow_methods'])
            ? $this->settings['cors']['allow_methods']
            : self::DEFAULT_METHODS;
    }
}
