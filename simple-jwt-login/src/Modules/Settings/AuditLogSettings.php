<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class AuditLogSettings extends BaseSettings implements SettingsInterface
{
    const SETTING_ENABLED         = 'enabled';
    const SETTING_ENABLED_EVENTS  = 'enabled_events';
    const SETTING_RETENTION_DAYS  = 'retention_days';

    const DEFAULT_RETENTION_DAYS = 90;

    protected function getSectionKey()
    {
        return 'audit_log';
    }

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            self::SETTING_ENABLED,
            'audit_log',
            self::SETTING_ENABLED,
            BaseSettings::SETTINGS_TYPE_BOL
        );

        $this->assignSettingsPropertyFromPost(
            null,
            self::SETTING_ENABLED_EVENTS,
            'audit_log',
            self::SETTING_ENABLED_EVENTS,
            BaseSettings::SETTINGS_TYPE_ARRAY
        );

        $this->assignSettingsPropertyFromPost(
            null,
            self::SETTING_RETENTION_DAYS,
            'audit_log',
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
        if (!isset($this->post['audit_log'])) {
            return;
        }

        $retention = isset($this->post['audit_log'][self::SETTING_RETENTION_DAYS])
            ? (int) $this->post['audit_log'][self::SETTING_RETENTION_DAYS]
            : self::DEFAULT_RETENTION_DAYS;

        if ($retention < 1) {
            throw new Exception(
                esc_html__('Audit log retention days must be at least 1.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUDIT_LOGS,
                    1
                ))
            );
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return !empty($this->settings[self::SETTING_ENABLED]);
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
     * @param string $eventType
     * @return bool
     */
    public function isAuditEventEnabled($eventType)
    {
        return $this->isEnabled() && $this->isEventEnabled($eventType);
    }

    /**
     * @return string[]
     */
    public function getEnabledEvents()
    {
        if (isset($this->settings[self::SETTING_ENABLED_EVENTS])
            && is_array($this->settings[self::SETTING_ENABLED_EVENTS])
        ) {
            return $this->settings[self::SETTING_ENABLED_EVENTS];
        }

        return [];
    }

    /**
     * @return int
     */
    public function getRetentionDays()
    {
        return isset($this->settings[self::SETTING_RETENTION_DAYS])
            ? (int) $this->settings[self::SETTING_RETENTION_DAYS]
            : self::DEFAULT_RETENTION_DAYS;
    }
}
