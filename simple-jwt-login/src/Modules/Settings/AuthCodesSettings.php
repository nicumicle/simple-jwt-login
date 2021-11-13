<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class AuthCodesSettings extends BaseSettings implements SettingsInterface
{
    const DEFAULT_AUTH_CODE_KEY = 'AUTH_KEY';

    public function initSettingsFromPost()
    {
        $authCodes = [];
        if (isset($this->post['auth_codes']) && isset($this->post['auth_codes']['code'])) {
            $codes = $this->post['auth_codes']['code'];
            foreach ($codes as $key => $code) {
                if (trim($code) === ''
                    || !isset($this->post['auth_codes']['role'][$key])
                    || !isset($this->post['auth_codes']['expiration_date'][$key])
                ) {
                    continue;
                }
                $authCodes[] = [
                    'code' => $this->wordPressData->sanitizeTextField($code),
                    'role' => $this->wordPressData->sanitizeTextField($this->post['auth_codes']['role'][$key]),
                    'expiration_date' => $this->wordPressData->sanitizeTextField(
                        $this->post['auth_codes']['expiration_date'][$key]
                    )
                ];
            }
        }
        $this->settings['auth_codes'] = $authCodes;

        $this->assignSettingsPropertyFromPost(
            null,
            'auth_code_key',
            null,
            'auth_code_key',
            BaseSettings::SETTINGS_TYPE_STRING
        );
    }

    public function validateSettings()
    {
        if (!empty($this->settings['require_login_auth'])
            && !empty($this->settings['allow_autologin'])
            || !empty($this->settings['require_register_auth'])
            && !empty($this->settings['allow_register'])
            || !empty($this->settings['require_delete_auth'])
            && !empty($this->settings['allow_delete'])
            || !empty($this->settings['auth_requires_auth_code'])
            && !empty($this->settings['allow_authentication'])
            || !empty($this->settings['reset_password_requires_auth_code'])
            && !empty($this->settings['allow_reset_password'])
        ) {
            if (empty($this->settings['auth_codes'])) {
                throw new Exception(
                    __(
                        'Missing Auth Codes. Please add at least one Auth Code.',
                        'simple-jwt-login'
                    ),
                    $this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTH_CODES,
                        SettingsErrors::ERR_EMPTY_AUTH_CODES
                    )
                );
            }
        }

        foreach ($this->settings['auth_codes'] as $code) {
            if (!empty($code['role']) && !$this->wordPressData->roleExists($code['role'])) {
                throw new Exception(
                    __(
                        'Invalid role provided.',
                        'simple-jwt-login'
                    ),
                    $this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTH_CODES,
                        SettingsErrors::ERR_INVALID_ROLE
                    )
                );
            }
        }
    }

    /**
     * @return array
     */
    public function getAuthCodes()
    {
        return isset($this->settings['auth_codes'])
            ? $this->settings['auth_codes']
            : [];
    }

    /**
     * @return string
     */
    public function getAuthCodeKey()
    {
        return !empty($this->settings['auth_code_key'])
            ? $this->settings['auth_code_key']
            : self::DEFAULT_AUTH_CODE_KEY;
    }
}
