<?php

namespace SimpleJWTLogin\Modules\Abilities;

use Exception;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\AuditLoggerService;
use WP_Error;

/**
 * Registers the Simple JWT Login abilities with the WordPress Abilities API
 * (WordPress 6.9+). Hooked to `wp_abilities_api_categories_init` and
 * `wp_abilities_api_init`, which never fire on older WordPress versions, so
 * nothing runs there.
 *
 * Read-only abilities are registered when the AI access mode allows reading,
 * the others when it allows writing. Every ability requires manage_options,
 * except explain-error, which any logged-in user ('read') can run.
 */
class AbilitiesRegistrar
{
    const CATEGORY            = 'simple-jwt-login';
    const ERROR_CODE          = 'simple_jwt_login_ability_error';

    /**
     * Capabilities an ability may require. Anything else (e.g. '' or
     * 'exist') is refused, so no ability can be opened to logged-out users.
     *
     * @var string[]
     */
    public static $allowedCapabilities = array('manage_options', 'read');

    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

    /**
     * @var AbilityCatalog
     */
    private $catalog;

    /**
     * @var AuditLoggerService
     */
    private $auditLogger;

    /**
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param AbilityCatalog         $catalog
     * @param AuditLoggerService     $auditLogger
     */
    public function __construct(
        SimpleJWTLoginSettings $jwtSettings,
        AbilityCatalog $catalog,
        AuditLoggerService $auditLogger
    ) {
        $this->jwtSettings = $jwtSettings;
        $this->catalog     = $catalog;
        $this->auditLogger = $auditLogger;
    }

    /**
     * @return void
     */
    public function registerCategory()
    {
        if (!$this->isAnyAccessAllowed()) {
            return;
        }

        $this->jwtSettings->getWordPressData()->registerAbilityCategory(self::CATEGORY, [
            'label'       => __('Simple JWT Login', 'simple-jwt-login'),
            'description' => __('Manage the Simple JWT Login plugin: settings, API keys, webhooks and the audit log.', 'simple-jwt-login'),
        ]);
    }

    /**
     * @return void
     */
    public function registerAbilities()
    {
        if (!$this->isAnyAccessAllowed()) {
            return;
        }

        $wordPressData = $this->jwtSettings->getWordPressData();
        foreach ($this->catalog->all() as $ability) {
            if (!$this->isAbilityAllowed($ability)) {
                continue;
            }
            $wordPressData->registerAbility($ability->getName(), $this->buildArgs($ability));
        }
    }

    /**
     * @param AbilityInterface $ability
     * @return array
     */
    public function buildArgs(AbilityInterface $ability)
    {
        $definition = $ability->getDefinition();
        $registrar  = $this;

        return [
            'label'               => $definition['label'],
            'description'         => $definition['description'],
            'category'            => self::CATEGORY,
            'input_schema'        => $definition['input_schema'],
            'output_schema'       => $definition['output_schema'],
            'permission_callback' => function () use ($registrar, $ability) {
                return $registrar->canExecute($ability);
            },
            'execute_callback'    => function ($input = null) use ($registrar, $ability) {
                return $registrar->executeAbility($ability, $input);
            },
            'meta'                => [
                'annotations'  => $definition['annotations'],
                'public'       => true,
                'show_in_rest' => true,
                'mcp'          => ['public' => true, 'type' => 'tool'],
            ],
        ];
    }

    /**
     * Settings are re-read from the database on every call, so changing the
     * access mode or the user's role takes effect immediately, even in a
     * long-running MCP stdio session.
     *
     * @param AbilityInterface $ability
     * @return bool
     */
    public function canExecute(AbilityInterface $ability)
    {
        $this->jwtSettings->reload();

        return $this->isExecutableByCurrentUser($ability);
    }

    /**
     * `rest_pre_dispatch` filter. Core lets any user with 'read' list the
     * registered abilities (REST /wp-abilities/v1 and MCP discovery), and a
     * hidden-but-registered ability answers /run with 403 instead of 404.
     * Both would tell a non-administrator which AI access mode and permissions
     * the site uses, so abilities the current user cannot run are removed
     * from the registry for this request.
     *
     * Skipped while routes are still being registered (plugins such as
     * WooCommerce dispatch internal requests from rest_api_init, before the
     * request's user is final) and in WP-CLI (shell access, and the user can
     * change after load): unregistering cannot be undone within the process.
     *
     * @param mixed $result
     * @return mixed $result, unchanged
     */
    public function filterRestPreDispatch($result)
    {
        $wordPressData = $this->jwtSettings->getWordPressData();
        if (!$wordPressData->isAbilitiesApiAvailable()
            || $wordPressData->isCli()
            || $wordPressData->doingAction('rest_api_init')
            || !$this->isAnyAccessAllowed()
            || $wordPressData->currentUserCan(AbstractAbility::CAPABILITY_ADMIN)
        ) {
            return $result;
        }

        if ($wordPressData->didAction('wp_abilities_api_init')) {
            $this->hideFromCurrentUser();
            return $result;
        }
        // The registry is built lazily: hide right after our abilities are registered.
        $wordPressData->addAction('wp_abilities_api_init', [$this, 'hideFromCurrentUser'], PHP_INT_MAX);

        return $result;
    }

    /**
     * Unregisters (for this request only) every ability the current user
     * cannot execute, and the category when none is left. Runs in web
     * requests only (see filterRestPreDispatch()), where the settings loaded
     * at boot are current: no reload, to keep REST requests cheap.
     *
     * @return void
     */
    public function hideFromCurrentUser()
    {
        $wordPressData = $this->jwtSettings->getWordPressData();

        $visible = 0;
        foreach ($this->catalog->all() as $ability) {
            if ($this->isExecutableByCurrentUser($ability)) {
                $visible++;
                continue;
            }
            $wordPressData->unregisterAbility($ability->getName());
        }

        if ($visible === 0) {
            $wordPressData->unregisterAbilityCategory(self::CATEGORY);
        }
    }

    /**
     * @param AbilityInterface $ability
     * @return bool
     */
    private function isExecutableByCurrentUser(AbilityInterface $ability)
    {
        $capability = (string) $ability->getRequiredCapability();

        return $this->isAbilityAllowed($ability)
            && in_array($capability, self::$allowedCapabilities, true)
            && $this->jwtSettings->getWordPressData()->currentUserCan($capability);
    }

    /**
     * @param AbilityInterface $ability
     * @param mixed            $input
     * @return array|WP_Error
     */
    public function executeAbility(AbilityInterface $ability, $input)
    {
        try {
            $this->jwtSettings->reload();
            $result = $ability->execute(is_array($input) ? $input : []);
        } catch (Exception $exception) {
            // Messages are HTML-escaped for the admin UI / REST; abilities return plain text.
            $message = html_entity_decode($exception->getMessage(), ENT_QUOTES, 'UTF-8');
            $this->audit($ability, 'failure', array_merge(
                $this->auditDetails($ability, $input, []),
                ['error' => $message]
            ));

            return new WP_Error(self::ERROR_CODE, $message, [
                'status'     => 400,
                'error_code' => $exception->getCode(),
            ]);
        }

        $this->audit($ability, 'success', $this->auditDetails($ability, $input, (array) $result));

        return $result;
    }

    /**
     * @param AbilityInterface $ability
     * @param mixed            $input
     * @param array            $result
     * @return array
     */
    private function auditDetails(AbilityInterface $ability, $input, array $result)
    {
        if (!$ability instanceof AuditableAbilityInterface) {
            return [];
        }

        return $ability->auditDetails(is_array($input) ? $input : [], $result);
    }

    /**
     * Only changes are audited; reads would flood the log.
     *
     * @param AbilityInterface $ability
     * @param string           $status
     * @param array            $details
     * @return void
     */
    private function audit(AbilityInterface $ability, $status, array $details)
    {
        if ($ability->isReadOnly()) {
            return;
        }

        $userId  = $this->jwtSettings->getWordPressData()->getCurrentUserId();
        $message = array_merge(['source' => 'ability', 'ability' => $ability->getName()], $details);
        // The logger was built at boot; use the audit settings reloaded for this call.
        $auditLogger = $this->auditLogger->withSettings($this->jwtSettings->getAuditLogSettings());
        $auditLogger->log(
            $status === 'success' ? AuditEvents::AI_ABILITY_SUCCESS : AuditEvents::AI_ABILITY_FAILED,
            $userId > 0 ? $userId : null,
            null,
            $status,
            (string) json_encode($message)
        );
    }

    /**
     * @param AbilityInterface $ability
     * @return bool
     */
    private function isAbilityAllowed(AbilityInterface $ability)
    {
        $aiSettings = $this->jwtSettings->getIntegrationsSettings()->aiAbilities();
        $permission = (string) $ability->getRequiredPermission();
        if ($permission !== '' && !$aiSettings->isPermissionGranted($permission)) {
            return false;
        }

        return $ability->isReadOnly()
            ? $aiSettings->isReadAllowed()
            : $aiSettings->isWriteAllowed();
    }

    /**
     * @return bool
     */
    private function isAnyAccessAllowed()
    {
        $aiSettings = $this->jwtSettings->getIntegrationsSettings()->aiAbilities();

        return $aiSettings->isReadAllowed() || $aiSettings->isWriteAllowed();
    }
}
