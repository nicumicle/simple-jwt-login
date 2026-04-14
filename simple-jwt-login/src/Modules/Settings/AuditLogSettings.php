<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class AuditLogSettings extends BaseSettings implements SettingsInterface
{
    const SETTINGS_GROUP          = 'audit_log';
    const SETTING_ENABLED         = 'enabled';
    const SETTING_ENABLED_EVENTS  = 'enabled_events';
    const SETTING_RETENTION_DAYS  = 'retention_days';

    const DEFAULT_RETENTION_DAYS = 90;

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            self::SETTINGS_GROUP,
            self::SETTING_ENABLED,
            self::SETTINGS_GROUP,
            self::SETTING_ENABLED,
            BaseSettings::SETTINGS_TYPE_BOL
        );

        $this->assignSettingsPropertyFromPost(
            self::SETTINGS_GROUP,
            self::SETTING_ENABLED_EVENTS,
            self::SETTINGS_GROUP,
            self::SETTING_ENABLED_EVENTS,
            BaseSettings::SETTINGS_TYPE_ARRAY
        );

        $this->assignSettingsPropertyFromPost(
            self::SETTINGS_GROUP,
            self::SETTING_RETENTION_DAYS,
            self::SETTINGS_GROUP,
            self::SETTING_RETENTION_DAYS,
            BaseSettings::SETTINGS_TYPE_INT,
            self::DEFAULT_RETENTION_DAYS
        );
    }

    /**
     * @throws Exception
     */
    public function validateSettings()
    {
        if (!isset($this->post[self::SETTINGS_GROUP])) {
            return;
        }

        $retention = isset($this->post[self::SETTINGS_GROUP][self::SETTING_RETENTION_DAYS])
            ? (int) $this->post[self::SETTINGS_GROUP][self::SETTING_RETENTION_DAYS]
            : self::DEFAULT_RETENTION_DAYS;

        if ($retention < 1) {
            throw new Exception(
                __('Audit log retention days must be at least 1.', 'simple-jwt-login'),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUDIT_LOGS,
                    1
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return isset($this->settings[self::SETTINGS_GROUP][self::SETTING_ENABLED])
            && (bool) $this->settings[self::SETTINGS_GROUP][self::SETTING_ENABLED] === true;
    }

    /**
     * @param string $eventType
     * @return bool
     */
    public function isEventEnabled($eventType)
    {
        $events = $this->getEnabledEvents();
        return in_array($eventType, $events, true);
    }

    /**
     * @return string[]
     */
    public function getEnabledEvents()
    {
        if (isset($this->settings[self::SETTINGS_GROUP][self::SETTING_ENABLED_EVENTS])
            && is_array($this->settings[self::SETTINGS_GROUP][self::SETTING_ENABLED_EVENTS])
        ) {
            return $this->settings[self::SETTINGS_GROUP][self::SETTING_ENABLED_EVENTS];
        }

        return [];
    }

    /**
     * @return int
     */
    public function getRetentionDays()
    {
        if (isset($this->settings[self::SETTINGS_GROUP][self::SETTING_RETENTION_DAYS])) {
            return (int) $this->settings[self::SETTINGS_GROUP][self::SETTING_RETENTION_DAYS];
        }

        return self::DEFAULT_RETENTION_DAYS;
    }
}
