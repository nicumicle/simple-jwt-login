<?php

namespace SimpleJWTLogin\Modules;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ApplicationsSettings;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Modules\Settings\AuthCodesSettings;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\CorsSettings;
use SimpleJWTLogin\Modules\Settings\DeleteUserSettings;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\HooksSettings;
use SimpleJWTLogin\Modules\Settings\JwtRulesSettings;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\Settings\RegisterSettings;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\Settings\SettingsFactory;
use SimpleJWTLogin\Modules\Settings\SettingsInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SimpleJWTLoginSettings
{
    const REVOKE_TOKEN_KEY = 'simple_jwt_login_revoked_token';
    const OPTIONS_KEY = 'simple_jwt_login_settings';

    /**
     * @var null|array
     */
    private $settings;

    /**
     * @var array
     */
    private $post;

    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    /**
     * @var boolean
     */
    private $needUpdateOnOptions;

    /**
     * @var SettingsInterface[]
     */
    private $settingsParsers = [];

    /**
     * @var array
     */
    private $lastSettingsDiff = [];

    /**
     * @var array
     */
    private static $settingsInstances = [];

    /**
     * SimpleJWTLoginSettings constructor.
     *
     * @param WordPressDataInterface $wordPressData
     */
    public function __construct(WordPressDataInterface $wordPressData)
    {
        $this->wordPressData = $wordPressData;
        $data = $this->wordPressData->getOptionFromDatabase(self::OPTIONS_KEY);
        $this->settings = [];
        if ($data !== null) {
            $this->settings = json_decode($data, true);
        }

        $this->needUpdateOnOptions = $data !== false;

        $this->post = [];
    }

    /**
     * @return WordPressDataInterface
     */
    public function getWordPressData()
    {
        return $this->wordPressData;
    }

    private function getSettingsClassByType($type)
    {
        if (isset(self::$settingsInstances[$type])) {
            return self::$settingsInstances[$type];
        }
        self::$settingsInstances[$type] = SettingsFactory::getFactory($type)
            ->withWordPressData($this->getWordPressData())
            ->withSettings($this->settings);

        return self::$settingsInstances[$type];
    }

    /**
     * @return GeneralSettings
     */
    public function getGeneralSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::GENERAL_SETTINGS);
    }

    /**
     * @return AuthCodesSettings
     */
    public function getAuthCodesSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::AUTH_CODES_SETTINGS);
    }

    /**
     * @return AuthenticationSettings
     */
    public function getAuthenticationSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::AUTHENTICATION_SETTINGS);
    }

    /**
     * @return DeleteUserSettings
     */
    public function getDeleteUserSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::DELETE_USER_SETTINGS);
    }

    /**
     * @return LoginSettings
     */
    public function getLoginSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::LOGIN_SETTINGS);
    }

    /**
     * @return RegisterSettings
     */
    public function getRegisterSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::REGISTER_SETTINGS);
    }

    /**
     * @return CorsSettings
     */
    public function getCorsSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::CORS_SETTINGS);
    }

    /**
     * @return HooksSettings
     */
    public function getHooksSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::HOOKS_SETTINGS);
    }

    /**
     * @return ResetPasswordSettings
     */
    public function getResetPasswordSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::RESET_PASSWORD_SETTINGS);
    }

    /**
     * @return ProtectEndpointSettings
     */
    public function getProtectEndpointsSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::PROTECT_ENDPOINTS_SETTINGS);
    }

    /**
     * @return ApplicationsSettings
     */
    public function getApplicationsSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::APPLICATIONS_SETTINGS);
    }

    /**
     * @return JwtRulesSettings
     */
    public function getJwtRulesSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::JWT_RULES_SETTINGS);
    }

    /**
     * @return WebhooksSettings
     */
    public function getWebhooksSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::WEBHOOKS_SETTINGS);
    }

    /**
     * @return AuditLogSettings
     */
    public function getAuditLogSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::AUDIT_LOG_SETTINGS);
    }

    /**
     * This function makes sure that when save is pressed, all the data is saved
     *
     * @param array $post
     *
     * @return bool
     * @throws Exception
     */
    public function watchForUpdates($post)
    {
        if (empty($post) || !isset($post['_wpnonce'])) {
            return false;
        }
        $result = $this->wordPressData
            ->checkNonce($post['_wpnonce'], WordPressRepository::NONCE_NAME);
        if ($result === false) {
            throw new Exception(
                'Something is wrong. We can not save the settings.',
                ErrorCodes::ERR_INVALID_NONCE
            );
        }

        $oldSettings = $this->settings !== null ? $this->settings : [];

        // WordPress applies wp_magic_quotes() to $_POST at boot; strip those slashes
        // before processing so values are stored clean and don't grow on each save.
        $this->post = $this->wordPressData->wpUnslash($post);
        $this->settingsParsers = (new SettingsFactory())->getAll();

        foreach ($this->settingsParsers as $oneParser) {
            $oneParser
                ->withPost($this->post)
                ->withSettings($this->settings)
                ->withWordPressData($this->wordPressData)
                ->initSettingsFromPost();
            if ($this->settings === null) {
                $this->settings = [];
            }
            $this->settings = array_replace($this->settings, $oneParser->getSettings());
            self::$settingsInstances = [];
        }
        self::$settingsInstances = [];
        $this->saveSettingsInDatabase();

        $this->lastSettingsDiff = $this->buildSettingsDiff($oldSettings, $this->settings);

        return true;
    }

    /**
     * Returns the diff computed during the last successful watchForUpdates call.
     *
     * @return array
     */
    public function getLastSettingsDiff()
    {
        return $this->lastSettingsDiff;
    }

    /**
     * Compute a flat diff between two settings arrays.
     *
     * @param array $old
     * @param array $new
     * @return array
     */
    public function buildSettingsDiff($old, $new)
    {
        $flatOld = $this->flattenSettings($old);
        $flatNew = $this->flattenSettings($new);

        $changed = [];
        $added   = [];
        $removed = [];

        foreach ($flatNew as $key => $value) {
            if (!array_key_exists($key, $flatOld)) {
                $added[] = $key;
            } elseif ($flatOld[$key] !== $value) {
                $changed[$key] = [
                    'from' => $this->redactIfSensitive($key, $flatOld[$key]),
                    'to'   => $this->redactIfSensitive($key, $value),
                ];
            }
        }

        foreach (array_keys($flatOld) as $key) {
            if (!array_key_exists($key, $flatNew)) {
                $removed[] = $key;
            }
        }

        return array_filter([
            'changed' => $changed,
            'added'   => $added,
            'removed' => $removed,
        ]);
    }

    /**
     * Flatten a nested settings array into dot-notation keys.
     * Indexed (list) arrays are serialised as JSON strings rather than recursed into.
     *
     * @param array  $settings
     * @param string $prefix
     * @return array<string, string>
     */
    private function flattenSettings($settings, $prefix = '')
    {
        $result = [];
        if (!is_array($settings)) {
            return $result;
        }
        foreach ($settings as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix . '.' . $key : (string) $key;
            if (is_array($value) && !empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
                $result = array_merge($result, $this->flattenSettings($value, $fullKey));
                continue;
            }
            $result[$fullKey] = is_array($value) ? (string) json_encode($value) : (string) $value;
        }
        return $result;
    }

    /**
     * Replace the value with '[REDACTED]' for keys that may hold sensitive data.
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    private function redactIfSensitive($key, $value)
    {
        $lowerKey = strtolower($key);
        foreach (['secret', 'password', '_key'] as $pattern) {
            if (strpos($lowerKey, $pattern) !== false) {
                return '[REDACTED]';
            }
        }
        return $value;
    }

    /**
     * Save Data
     * @throws Exception
     */
    private function saveSettingsInDatabase()
    {
        foreach ($this->settingsParsers as $oneParser) {
            $oneParser
                ->withPost($this->post)
                ->withSettings($this->settings)
                ->validateSettings();
        }

        if ($this->needUpdateOnOptions) {
            return $this->wordPressData->updateOption(self::OPTIONS_KEY, json_encode($this->settings));
        }

        return $this->wordPressData->addOption(self::OPTIONS_KEY, json_encode($this->settings));
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @param string $route
     * @param array $params
     *
     * @return string
     */
    public function generateExampleLink($route, $params)
    {
        $url = $this->wordPressData->getSiteUrl()
            . '/?rest_route=/'
            . $this->getGeneralSettings()->getRouteNamespace()
            . $route;

        if (empty($params) || !is_array($params)) {
            return $url;
        }

        foreach ($params as $key => $value) {
            $url .= sprintf(
                '&%s=%s',
                $key,
                $value
            );
        }

        return $url;
    }
}
