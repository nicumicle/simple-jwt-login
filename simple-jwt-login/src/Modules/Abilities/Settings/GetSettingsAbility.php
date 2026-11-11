<?php

namespace SimpleJWTLogin\Modules\Abilities\Settings;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\AbstractAbility;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;
use SimpleJWTLogin\Modules\Abilities\SettingsSections;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class GetSettingsAbility extends AbstractAbility
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
        return 'get-settings';
    }

    public function isReadOnly()
    {
        return true;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Get Simple JWT Login settings', 'simple-jwt-login'),
            'description'   => __(
                'Returns the Simple JWT Login plugin settings, either all sections or a single one. Secrets (JWT signing keys, OAuth client secrets, refresh token key, auth code values, webhook header values) are masked as "********" unless the administrator enabled "Allow viewing secrets" (RSA/ECDSA keys, decryption_key_public / decryption_key_private, are then returned base64-encoded, as stored). The response also lists the secret keys and paths, the setting paths that cannot be changed through simple-jwt-login/update-settings right now, and ai_permissions: the opt-in permissions the administrator granted (only the administrator can change them). Use simple-jwt-login/explain-error to map an error_code to the settings behind it.',
                'simple-jwt-login'
            ),
            'input_schema'  => $this->objectSchema([
                'section' => [
                    'type'        => 'string',
                    'enum'        => SettingsSections::all(),
                    'description' => __('Optional settings section to return. Omit to return every section.', 'simple-jwt-login'),
                ],
            ]),
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'settings'        => ['type' => 'object'],
                    'read_only_paths' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'secret_keys'     => ['type' => 'array', 'items' => ['type' => 'string']],
                    'secret_paths'    => ['type' => 'array', 'items' => ['type' => 'string']],
                    'ai_permissions'  => ['type' => 'object'],
                ],
            ],
            'annotations'   => $this->annotations(true, false, true),
        ];
    }

    public function execute(array $input)
    {
        $policy   = $this->policy->withPermissions($this->aiSettings());
        $redacted = (new SettingsRedactor($policy))->redact($this->jwtSettings->getStoredSettings());
        unset($redacted['_schema_version']);

        $section = $this->inputValue($input, 'section');
        if ($section !== null) {
            if (!isset($redacted[$section])) {
                throw new Exception(
                    esc_html__('Unknown settings section.', 'simple-jwt-login'),
                    absint(ErrorCodes::ERR_ABILITY_UNKNOWN_SECTION)
                );
            }
            $redacted = [$section => $redacted[$section]];
        }

        return [
            'settings'        => (object) $redacted,
            'read_only_paths' => $policy->readOnlyPaths(),
            'secret_keys'     => AbilitySettingsPolicy::$secretLeafKeys,
            'secret_paths'    => AbilitySettingsPolicy::$secretPaths,
            'ai_permissions'  => $policy->getPermissions(),
        ];
    }
}
