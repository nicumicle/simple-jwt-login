<?php

namespace SimpleJWTLogin\Modules\Settings;

class HooksSettings extends BaseSettings implements SettingsInterface
{
    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'enabled_hooks',
            null,
            'enabled_hooks',
            BaseSettings::SETTINGS_TYPE_ARRAY
        );
    }

    public function validateSettings()
    {
    }

    /**
     * @return array
     */
    public function getEnabledHooks()
    {
        return isset($this->settings['enabled_hooks'])
            ? (array)$this->settings['enabled_hooks']
            : [];
    }

    /**
     * @param string $hookName
     * @return bool
     */
    public function isHookEnable($hookName)
    {
        return !empty($this->settings['enabled_hooks'])
            && in_array($hookName, $this->settings['enabled_hooks']);
    }
}
