<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class CorsSettings extends BaseSettings implements SettingsInterface
{
    const DEFAULT_HEADER_PARAMETER = '*';
    const DEFAULT_METHODS = 'GET, POST, PUT, DELETE, OPTIONS, HEAD';

    protected function getSectionKey()
    {
        return 'cors';
    }

    protected function getFieldDefinitions()
    {
        return [
            [null, 'enabled',               'cors', 'enabled',               self::SETTINGS_TYPE_INT],
            [null, 'allow_origin_enabled',  'cors', 'allow_origin_enabled',  self::SETTINGS_TYPE_BOL, false],
            [null, 'allow_origin',          'cors', 'allow_origin',          self::SETTINGS_TYPE_STRING],
            [null, 'allow_methods_enabled', 'cors', 'allow_methods_enabled', self::SETTINGS_TYPE_BOL, false],
            [null, 'allow_methods',         'cors', 'allow_methods',         self::SETTINGS_TYPE_STRING],
            [null, 'allow_headers_enabled', 'cors', 'allow_headers_enabled', self::SETTINGS_TYPE_BOL, false],
            [null, 'allow_headers',         'cors', 'allow_headers',         self::SETTINGS_TYPE_STRING],
        ];
    }

    public function validateSettings()
    {
        if (!empty($this->settings['enabled'])
            && (
                empty($this->settings['allow_origin_enabled'])
                && empty($this->settings['allow_methods_enabled'])
                && empty($this->settings['allow_headers_enabled'])
            )
        ) {
            throw new Exception(
                esc_html__(
                    'Cors is enabled but no option is checked. Please check at least one option.',
                    'simple-jwt-login'
                ),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_CORS,
                    SettingsErrors::ERR_CORS_NO_OPTION
                ))
            );
        }
    }

    /**
     * @return bool
     */
    public function isCorsEnabled()
    {
        return !empty($this->settings['enabled']);
    }

    /**
     * @return bool
     */
    public function isAllowOriginEnabled()
    {
        return isset($this->settings['allow_origin_enabled'])
            && filter_var($this->settings['allow_origin_enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return string
     */
    public function getAllowOrigin()
    {
        return isset($this->settings['allow_origin'])
            ? $this->settings['allow_origin']
            : '';
    }

    /**
     * The configured origins, split on commas and whitespace.
     *
     * @return array
     */
    public function getAllowedOrigins()
    {
        $origins = preg_split('/[\s,]+/', (string) $this->getAllowOrigin(), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_map(function ($origin) {
            return $origin === '*' ? $origin : rtrim($origin, '/');
        }, $origins));
    }

    /**
     * The Access-Control-Allow-Origin value for a request origin, or null when
     * that origin is not allowed. A listed origin is echoed back, since the
     * header accepts a single origin only. A "*" entry allows every origin and
     * echoes it too, so credentialed requests keep working.
     *
     * @param string|null $requestOrigin
     *
     * @return string|null
     */
    public function resolveAllowOrigin($requestOrigin)
    {
        $allowedOrigins = $this->getAllowedOrigins();
        $requestOrigin = trim((string) $requestOrigin);

        if (in_array('*', $allowedOrigins, true)) {
            return $requestOrigin !== '' ? $requestOrigin : '*';
        }
        if ($requestOrigin === '') {
            return null;
        }

        foreach ($allowedOrigins as $allowedOrigin) {
            if (strcasecmp($allowedOrigin, rtrim($requestOrigin, '/')) === 0) {
                return $requestOrigin;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isAllowHeadersEnabled()
    {
        return isset($this->settings['allow_headers_enabled'])
            && filter_var($this->settings['allow_headers_enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return string
     */
    public function getAllowHeaders()
    {
        return isset($this->settings['allow_headers'])
            ? $this->settings['allow_headers']
            : self::DEFAULT_HEADER_PARAMETER;
    }

    /**
     * @return bool
     */
    public function isAllowMethodsEnabled()
    {
        return isset($this->settings['allow_methods_enabled'])
            && filter_var($this->settings['allow_methods_enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return string
     */
    public function getAllowMethods()
    {
        return isset($this->settings['allow_methods'])
            ? $this->settings['allow_methods']
            : self::DEFAULT_METHODS;
    }
}
