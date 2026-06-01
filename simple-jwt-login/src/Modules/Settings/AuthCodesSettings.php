<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class AuthCodesSettings extends BaseSettings implements SettingsInterface
{
    const DEFAULT_AUTH_CODE_KEY = 'AUTH_KEY';

    protected function getSectionKey()
    {
        return 'auth_codes';
    }

    public function initSettingsFromPost()
    {
        $codes = [];
        if (isset($this->post['auth_codes']['code'])) {
            foreach ($this->post['auth_codes']['code'] as $key => $code) {
                if (trim($code) === ''
                    || !isset($this->post['auth_codes']['role'][$key])
                    || !isset($this->post['auth_codes']['expiration_date'][$key])
                ) {
                    continue;
                }
                $codes[] = [
                    'code' => $this->wordPressData->sanitizeTextField($code),
                    'role' => $this->wordPressData->sanitizeTextField($this->post['auth_codes']['role'][$key]),
                    'expiration_date' => $this->wordPressData->sanitizeTextField(
                        $this->post['auth_codes']['expiration_date'][$key]
                    )
                ];
            }
        }
        $this->settings['codes'] = $codes;

        $this->assignSettingsPropertyFromPost(
            null,
            'key',
            null,
            'auth_code_key',
            BaseSettings::SETTINGS_TYPE_STRING
        );
    }

    public function validateSettings()
    {
        $loginEnabled   = !empty($this->fullSettings['login']['enabled']);
        $loginAuthCode  = !empty($this->fullSettings['login']['auth_code']);
        $regEnabled     = !empty($this->fullSettings['register']['enabled']);
        $regAuthCode    = !empty($this->fullSettings['register']['auth_code']);
        $deleteEnabled  = !empty($this->fullSettings['delete_user']['enabled']);
        $deleteAuthCode = !empty($this->fullSettings['delete_user']['auth_code']);
        $authEnabled    = !empty($this->fullSettings['authorization']['enabled']);
        $authAuthCode   = !empty($this->fullSettings['authorization']['auth_code']);
        $rpEnabled      = !empty($this->fullSettings['reset_password']['enabled']);
        $rpAuthCode     = !empty($this->fullSettings['reset_password']['auth_code']);

        if (($loginAuthCode && $loginEnabled)
            || ($regAuthCode && $regEnabled)
            || ($deleteAuthCode && $deleteEnabled)
            || ($authAuthCode && $authEnabled)
            || ($rpAuthCode && $rpEnabled)
        ) {
            if (empty($this->settings['codes'])) {
                throw new Exception(
                    esc_html__(
                        'Missing Auth Codes. Please add at least one Auth Code.',
                        'simple-jwt-login'
                    ),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTH_CODES,
                        SettingsErrors::ERR_EMPTY_AUTH_CODES
                    ))
                );
            }
        }

        foreach ($this->settings['codes'] as $code) {
            if (!empty($code['role']) && !$this->wordPressData->roleExists($code['role'])) {
                throw new Exception(
                    esc_html__(
                        'Invalid role provided.',
                        'simple-jwt-login'
                    ),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTH_CODES,
                        SettingsErrors::ERR_INVALID_ROLE
                    ))
                );
            }
        }
    }

    /**
     * @return array
     */
    public function getAuthCodes()
    {
        return isset($this->settings['codes'])
            ? $this->settings['codes']
            : [];
    }

    /**
     * @return string
     */
    public function getAuthCodeKey()
    {
        return !empty($this->settings['key'])
            ? $this->settings['key']
            : self::DEFAULT_AUTH_CODE_KEY;
    }
}
