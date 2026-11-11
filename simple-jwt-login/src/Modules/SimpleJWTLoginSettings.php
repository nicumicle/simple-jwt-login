<?php

namespace SimpleJWTLogin\Modules;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ApiKeysSettings;
use SimpleJWTLogin\Modules\Settings\IntegrationsSettings;
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
use SimpleJWTLogin\Modules\Settings\ThemeSettings;
use SimpleJWTLogin\Modules\Settings\Migrations\SettingsMigrationService;
use SimpleJWTLogin\Modules\Settings\SettingsDiff;
use SimpleJWTLogin\Modules\Settings\SettingsFactory;
use SimpleJWTLogin\Modules\Settings\SettingsInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SimpleJWTLoginSettings
{
    const REVOKE_TOKEN_KEY = 'simple_jwt_login_revoked_token';
    const OPTIONS_KEY = 'simple_jwt_login_settings';
    const SCHEMA_VERSION = 2;

    /**
     * @var null|array
     */
    private $settings;

    /**
     * @var array
     */
    private $post;

    /**
     * @var Repository
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
     * @param Repository $wordPressData
     */
    public function __construct($wordPressData)
    {
        $this->wordPressData = $wordPressData;
        $this->loadSettings($this->wordPressData->getOptionFromDatabase(self::OPTIONS_KEY));

        $this->post = [];
    }

    /**
     * Re-read the settings from the database, bypassing every cache.
     * Long-running processes (e.g. an MCP stdio session) call this so changes
     * made elsewhere (wp-admin) are seen and not overwritten.
     *
     * @return void
     */
    public function reload()
    {
        $this->loadSettings($this->wordPressData->getFreshOption(self::OPTIONS_KEY));
        self::$settingsInstances = [];
    }

    /**
     * @param mixed $data Raw option value.
     * @return void
     */
    private function loadSettings($data)
    {
        $this->settings = [];
        if ($data !== null) {
            $raw = json_decode((string) $data, true);
            $this->settings = SettingsMigrationService::migrate(
                is_array($raw) ? $raw : []
            );
        }

        $this->needUpdateOnOptions = $data !== false;
    }

    /**
     * @return Repository
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
     * @return IntegrationsSettings
     */
    public function getIntegrationsSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::INTEGRATIONS_SETTINGS);
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
     * @return ApiKeysSettings
     */
    public function getApiKeysSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::API_KEYS_SETTINGS);
    }

    /**
     * @return ThemeSettings
     */
    public function getThemeSettings()
    {
        return $this->getSettingsClassByType(SettingsFactory::THEME_SETTINGS);
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
                esc_html__('Something is wrong. We can not save the settings.', 'simple-jwt-login'),
                absint(ErrorCodes::ERR_INVALID_NONCE)
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
        $this->settings['_schema_version'] = self::SCHEMA_VERSION;
        self::$settingsInstances = [];
        $this->saveSettingsInDatabase();

        $this->lastSettingsDiff = $this->buildSettingsDiff($oldSettings, $this->settings);

        return true;
    }

    /**
     * Returns the full settings array as it is stored (after migrations).
     *
     * @return array
     */
    public function getStoredSettings()
    {
        return $this->settings !== null ? $this->settings : [];
    }

    /**
     * Validate and persist a complete settings array, bypassing the admin form.
     *
     * Used by programmatic callers (e.g. the WordPress Abilities API) that edit
     * the stored array directly. The admin-form POST is rebuilt from the new
     * array so every settings class runs exactly the validation it runs on a
     * form save. Nothing changes (in memory or in the database) when
     * validation fails.
     *
     * @param array $newSettings Complete settings array to store.
     * @return array Diff between the old and the new settings.
     * @throws Exception
     */
    public function saveSettingsArray(array $newSettings)
    {
        $oldSettings = $this->getStoredSettings();
        $newSettings['_schema_version'] = self::SCHEMA_VERSION;

        $parsers = (new SettingsFactory())->getAll();
        $post    = [];
        foreach ($parsers as $oneParser) {
            $oneParser
                ->withSettings($newSettings)
                ->withWordPressData($this->wordPressData);
            $post = array_replace_recursive($post, $oneParser->buildPostFromSettings());
        }

        foreach ($parsers as $oneParser) {
            $oneParser->withPost($post)->validateSettings();
        }

        $this->settings = $newSettings;
        self::$settingsInstances = [];
        $this->persistSettings();
        $this->needUpdateOnOptions = true;

        $this->lastSettingsDiff = $this->buildSettingsDiff($oldSettings, $this->settings);

        return $this->lastSettingsDiff;
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
        return (new SettingsDiff())->build($old, $new);
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

        return $this->persistSettings();
    }

    /**
     * @return bool
     */
    private function persistSettings()
    {
        if ($this->needUpdateOnOptions) {
            return $this->wordPressData->updateOption(self::OPTIONS_KEY, json_encode($this->settings));
        }

        return $this->wordPressData->addOption(self::OPTIONS_KEY, json_encode($this->settings));
    }

    /**
     * @param string $route
     * @param array $params
     *
     * @return string
     */
    public function generateExampleLink($route, $params)
    {
        $namespace = $this->getGeneralSettings()->getRouteNamespace();
        $permalinkStructure = $this->wordPressData->getOptionFromDatabase('permalink_structure');

        $url = $this->wordPressData->getSiteUrl()
            . ( !empty($permalinkStructure) ? '/wp-json/' : '/?rest_route=/')
            . $namespace
            . $route;
        $separator = !empty($permalinkStructure) ? '?' : '&';

        if (empty($params) || !is_array($params)) {
            return $url;
        }

        foreach ($params as $key => $value) {
            $url .= sprintf(
                '%s%s=%s',
                $separator,
                $key,
                $value
            );
            $separator = '&';
        }

        return $url;
    }
}
