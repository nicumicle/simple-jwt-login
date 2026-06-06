<?php

namespace SimpleJWTLogin\Modules\Settings;

class ThemeSettings extends BaseSettings implements SettingsInterface
{
    const MODE_DARK  = 'dark';
    const MODE_LIGHT = 'light';

    protected function getSectionKey()
    {
        return 'theme';
    }

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'mode',
            'theme',
            'mode',
            BaseSettings::SETTINGS_TYPE_STRING
        );
    }

    public function validateSettings()
    {
        if (isset($this->settings['mode'])
            && !in_array($this->settings['mode'], array(self::MODE_DARK, self::MODE_LIGHT), true)
        ) {
            $this->settings['mode'] = '';
        }
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return isset($this->settings['mode']) ? (string) $this->settings['mode'] : '';
    }
}
