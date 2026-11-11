<?php

namespace SimpleJWTLogin\Modules\Abilities\Settings;

use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\AbstractAbility;
use SimpleJWTLogin\Modules\Abilities\AuditableAbilityInterface;
use SimpleJWTLogin\Modules\Abilities\SettingsPatcher;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;
use SimpleJWTLogin\Modules\Abilities\SettingsSections;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class UpdateSettingsAbility extends AbstractAbility implements AuditableAbilityInterface
{
    /**
     * @var AbilitySettingsPolicy
     */
    private $policy;

    /**
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param AbilitySettingsPolicy  $policy
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings, AbilitySettingsPolicy $policy)
    {
        parent::__construct($jwtSettings);
        $this->policy = $policy;
    }

    protected function getSlug()
    {
        return 'update-settings';
    }

    public function isReadOnly()
    {
        return false;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Update Simple JWT Login settings', 'simple-jwt-login'),
            'description'   => __(
                'Changes settings in one section of the Simple JWT Login plugin. Pass only the keys to change as a nested object that mirrors the structure returned by simple-jwt-login/get-settings (e.g. {"section":"login","values":{"enabled":true}}). Only existing keys can be changed and values are cast to the stored type. The new settings are validated exactly like a save from the admin page; on error nothing is saved. Lists of objects (auth_codes.codes, jwt_rules.rules) take a list of operations instead of a new list: {"index":0,"match":{"role":"subscriber"},"set":{"role":"editor"}} edits an item, {"index":0,"match":{...},"remove":true} removes it; "match" must equal the stored non-secret values, otherwise nothing is saved. New auth codes and JWT rules need secrets, so they can only be added from the admin page. reset_password.email_body is plain text/HTML. Secrets (see secret_keys and secret_paths in simple-jwt-login/get-settings), webhook items (use the webhook abilities) and the AI Abilities settings are read-only. Role settings (register.new_user_profile, auth_codes.codes.*.role) can only be changed when the administrator enabled "Allow changing roles". OAuth providers (integrations.oauth.<provider>), JWT rules (jwt_rules), the JWT-to-user mapping (login.login_by, login.login_by_parameter, login.iss_whitelist) and two-factor (integrations.3rdparty.two_factor) need "Allow changing authentication providers"; read_only_paths in simple-jwt-login/get-settings lists what is read-only right now.',
                'simple-jwt-login'
            ),
            'input_schema'  => [
                'type'                 => 'object',
                'properties'           => [
                    'section' => [
                        'type'        => 'string',
                        'enum'        => SettingsSections::writable($this->policy),
                        'description' => __('Settings section to change.', 'simple-jwt-login'),
                    ],
                    'values'  => [
                        'type'        => 'object',
                        'description' => __('Nested map of setting keys to their new values.', 'simple-jwt-login'),
                    ],
                ],
                'required'             => ['section', 'values'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'section'  => ['type' => 'string'],
                    'diff'     => ['type' => 'object'],
                    'settings' => ['type' => 'object'],
                ],
            ],
            'annotations'   => $this->annotations(false, true, true),
        ];
    }

    public function auditDetails(array $input, array $result)
    {
        $details = ['section' => (string) $this->inputValue($input, 'section', '')];
        if (isset($result['diff'])) {
            $details['diff'] = $result['diff'];
        }

        return $details;
    }

    public function execute(array $input)
    {
        $section = $this->inputValue($input, 'section');
        $policy  = $this->policy->withPermissions($this->aiSettings());
        $patcher = new SettingsPatcher($policy, $this->jwtSettings->getWordPressData());

        $newSettings = $patcher->apply(
            $this->jwtSettings->getStoredSettings(),
            $section,
            $this->inputValue($input, 'values')
        );
        // SettingsDiff always reports masked values (it feeds the audit log), so the diff is safe to return.
        $diff     = $this->jwtSettings->saveSettingsArray($newSettings);
        $redacted = (new SettingsRedactor($policy))->redact($this->jwtSettings->getStoredSettings());

        return [
            'section'  => (string) $section,
            'diff'     => (object) $diff,
            'settings' => (object) $redacted[$section],
        ];
    }
}
