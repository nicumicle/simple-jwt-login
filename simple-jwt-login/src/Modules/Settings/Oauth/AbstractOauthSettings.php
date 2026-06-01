<?php

namespace SimpleJWTLogin\Modules\Settings\Oauth;

use Exception;
use SimpleJWTLogin\Modules\Settings\BaseSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\Settings\SettingsValueCasterTrait;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

/**
 * Base class for every OAuth / OIDC provider settings block.
 *
 * Subclasses declare their own data via four hook methods:
 *
 *  - getExtraFields()                 - additional fields beyond the common set
 *  - getToggleableFeatures()          - feature-flag field names ("at least one" check)
 *  - getRequiredFieldValidations()    - [field, errorCode, label] tuples
 *  - getAtLeastOneEnabledErrorCode()  - code used in the "at least one" exception
 *  - getRedirectUriRequiredErrorCode()- code used when redirect URI is missing
 *
 * Registering a new provider only requires:
 *  1. Create a subclass that fills in the five hooks above.
 *  2. Add provider-specific getters as needed.
 *  3. Register the class in IntegrationsSettings::buildProviders().
 */
abstract class AbstractOauthSettings
{
    use SettingsValueCasterTrait;

    /** @var array<string, mixed> */
    private $data = [];

    /** @var SettingsErrors */
    protected $settingsErrors;

    public function __construct()
    {
        $this->settingsErrors = new SettingsErrors();
    }

    // =========================================================================
    // Abstract identity - subclasses must implement these
    // =========================================================================

    /**
     * Settings group key (also used as the HTML field prefix, e.g. "google").
     *
     * @return string
     */
    abstract public function getGroup();

    /**
     * Human-readable provider name used in error messages (e.g. "Google").
     *
     * @return string
     */
    abstract public function getName();

    // =========================================================================
    // Hook methods - override in subclasses to customise behaviour
    // =========================================================================

    /**
     * Additional fields specific to this provider, beyond the common set.
     * Format: [['field_name', BaseSettings::SETTINGS_TYPE_*], …]
     *
     * @return array<array{0: string, 1: int}>
     */
    protected function getExtraFields()
    {
        return [];
    }

    /**
     * Field names that count as "enabled features".
     * Validation fails when the provider is enabled but none of these are set.
     *
     * @return string[]
     */
    protected function getToggleableFeatures()
    {
        return ['enable_oauth', 'enable_exchange_code'];
    }

    /**
     * Required-field validation rules.
     * Format: [['field_name', SettingsErrors::ERR_*, 'Human Label'], …]
     *
     * @return array<array{0: string, 1: int, 2: string}>
     */
    abstract protected function getRequiredFieldValidations();

    /**
     * SettingsErrors constant used when no feature toggle is enabled.
     *
     * @return int
     */
    abstract protected function getAtLeastOneEnabledErrorCode();

    /**
     * SettingsErrors constant used when exchange_code is on but redirect_uri is missing.
     *
     * @return int
     */
    abstract protected function getRedirectUriRequiredErrorCode();

    // =========================================================================
    // Lifecycle - called by IntegrationsSettings
    // =========================================================================

    /**
     * Read the provider's POST slice and return a sanitised settings array.
     *
     * @param array $post Full POST array.
     * @param WordPressDataInterface $wpData
     * @return array<string, mixed>
     */
    public function processPost($post, WordPressDataInterface $wpData)
    {
        $group  = $this->getGroup();
        $result = [];

        foreach ($this->allFields() as $field) {
            list($name, $type) = $field;
            $raw               = isset($post[$group][$name]) ? $post[$group][$name] : null;
            $result[$name]     = $this->castValue($raw, $type, $wpData);
        }

        return $result;
    }

    /**
     * Validate the provider's POST slice. Throws on the first error found.
     *
     * @param array $post Full POST array.
     * @return void
     * @throws Exception
     */
    public function validate($post)
    {
        $group     = $this->getGroup();
        $groupPost = isset($post[$group]) ? $post[$group] : [];

        if (empty($groupPost['enabled'])) {
            return;
        }

        $this->checkAtLeastOneFeatureEnabled($groupPost);
        $this->checkRequiredFields($groupPost);
        $this->checkRedirectUri($groupPost);
    }

    /**
     * Hydrate this object with the stored settings slice for this provider.
     * Returns $this so it can be chained.
     *
     * @param array<string, mixed> $settings
     * @return $this
     */
    public function withSettings($settings)
    {
        $this->data = $settings;

        return $this;
    }

    // =========================================================================
    // Common getters - available on every provider
    // =========================================================================

    /** @return bool */
    public function isEnabled()
    {
        return !empty($this->data['enabled']);
    }

    /** @return string */
    public function getClientId()
    {
        return isset($this->data['client_id']) ? $this->data['client_id'] : '';
    }

    /** @return string */
    public function getClientSecret()
    {
        return isset($this->data['client_secret']) ? $this->data['client_secret'] : '';
    }

    /** @return bool */
    public function isAllowedOnAllEndpoints()
    {
        return !empty($this->data['allow_on_all_endpoints']);
    }

    /** @return bool */
    public function isCreateUserIfNotExistsEnabled()
    {
        return !empty($this->data['create_user_if_not_exists']);
    }

    /** @return bool */
    public function isOauthEnabled()
    {
        return !empty($this->data['enable_oauth']);
    }

    /** @return bool */
    public function isExchangeCodeEnabled()
    {
        return !empty($this->data['enable_exchange_code']);
    }

    /** @return string */
    public function getExchangeCodeRedirectUri()
    {
        return isset($this->data['redirect_uri_exchange_code']) ? $this->data['redirect_uri_exchange_code'] : '';
    }

    // =========================================================================
    // Escape hatches for provider-specific fields
    // =========================================================================

    /**
     * Generic string getter for extra fields.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function get($key, $default = '')
    {
        return (string)(isset($this->data[$key]) ? $this->data[$key] : $default);
    }

    /**
     * Generic boolean getter for extra fields.
     *
     * @param string $key
     * @return bool
     */
    public function isFieldEnabled($key)
    {
        return !empty($this->data[$key]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * @return array<array{0: string, 1: int}>
     */
    private function allFields()
    {
        return array_merge($this->commonFields(), $this->getExtraFields());
    }

    /**
     * @return array<array{0: string, 1: int}>
     */
    private function commonFields()
    {
        return [
            ['enabled',                    BaseSettings::SETTINGS_TYPE_INT],
            ['client_id',                  BaseSettings::SETTINGS_TYPE_STRING],
            ['client_secret',              BaseSettings::SETTINGS_TYPE_STRING],
            ['allow_on_all_endpoints',     BaseSettings::SETTINGS_TYPE_BOL],
            ['create_user_if_not_exists',  BaseSettings::SETTINGS_TYPE_BOL],
            ['enable_oauth',               BaseSettings::SETTINGS_TYPE_BOL],
            ['enable_exchange_code',       BaseSettings::SETTINGS_TYPE_BOL],
            ['redirect_uri_exchange_code', BaseSettings::SETTINGS_TYPE_STRING],
        ];
    }

    /**
     * @param array $groupPost Provider's POST slice.
     * @throws Exception
     */
    private function checkAtLeastOneFeatureEnabled($groupPost)
    {
        foreach ($this->getToggleableFeatures() as $feature) {
            if (!empty($groupPost[$feature])) {
                return;
            }
        }

        throw new Exception(
            esc_html(sprintf(
                // translators: 1: provider name, 2: provider name
                __(
                    'You need to enable at least one %1$s option in order to enable the %2$s App.',
                    'simple-jwt-login'
                ),
                $this->getName(),
                $this->getName()
            )),
            absint($this->settingsErrors->generateCode(
                SettingsErrors::PREFIX_APPLICATIONS,
                $this->getAtLeastOneEnabledErrorCode()
            ))
        );
    }

    /**
     * @param array $groupPost
     * @throws Exception
     */
    private function checkRequiredFields($groupPost)
    {
        foreach ($this->getRequiredFieldValidations() as $validation) {
            list($field, $errorCode, $label) = $validation;
            if (empty($groupPost[$field])) {
                throw new Exception(
                    esc_html(sprintf(
                        // translators: %s = field label (e.g. "Google Client ID")
                        __('%1$s is required.', 'simple-jwt-login'),
                        $label
                    )),
                    absint($this->settingsErrors->generateCode(SettingsErrors::PREFIX_APPLICATIONS, $errorCode))
                );
            }
        }
    }

    /**
     * @param array $groupPost
     * @throws Exception
     */
    private function checkRedirectUri($groupPost)
    {
        if (!empty($groupPost['enable_exchange_code']) && empty($groupPost['redirect_uri_exchange_code'])) {
            throw new Exception(
                esc_html(sprintf(
                    // translators: %s = provider name
                    __('%1$s Redirect URI is required when exchange code is enabled.', 'simple-jwt-login'),
                    $this->getName()
                )),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_APPLICATIONS,
                    $this->getRedirectUriRequiredErrorCode()
                ))
            );
        }
    }
}
