<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use SimpleJWTLogin\Modules\Abilities\SettingsPath;
use Exception;
use ReflectionClass;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\StatusCodeHelper;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\AbstractAbility;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

/**
 * Maps an error_code (from a REST response or a failed settings save) to its
 * meaning and the current, redacted values of the settings behind it, so an
 * agent can turn a pasted error into a concrete settings change.
 */
class ExplainErrorAbility extends AbstractAbility
{
    const KIND_REST     = 'rest';
    const KIND_SETTINGS = 'settings_validation';

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
        return 'explain-error';
    }

    public function isReadOnly()
    {
        return true;
    }

    /**
     * Any logged-in user can ask what an error means; only administrators
     * also get the current setting values (see result()).
     *
     * @return string
     */
    public function getRequiredCapability()
    {
        return self::CAPABILITY_USER;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Explain a Simple JWT Login error', 'simple-jwt-login'),
            'description'   => __(
                'Explains an error_code returned by a Simple JWT Login REST endpoint (e.g. {"success":false,"data":{"message":"...","error_code":48}}) or by a failed settings save (codes of 1000 and above, e.g. 2005). Returns the error constant, HTTP status, likely causes, the paths of the related settings and, for administrators only, their current values (secrets masked). Any logged-in user can run it. Use it when someone pastes an error, then fix the cause with simple-jwt-login/update-settings.',
                'simple-jwt-login'
            ),
            'input_schema'  => [
                'type'                 => 'object',
                'properties'           => [
                    'error_code' => ['type' => 'integer', 'minimum' => 1],
                ],
                'required'             => ['error_code'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'error_code'       => ['type' => 'integer'],
                    'kind'             => ['type' => 'string', 'enum' => [self::KIND_REST, self::KIND_SETTINGS]],
                    'constant'         => ['type' => ['string', 'null']],
                    'http_status'      => ['type' => ['integer', 'null']],
                    'explanation'      => ['type' => 'string'],
                    'related_settings' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'settings'         => ['type' => 'object'],
                ],
            ],
            'annotations'   => $this->annotations(true, false, true),
        ];
    }

    public function execute(array $input)
    {
        $code     = (int) $this->inputValue($input, 'error_code', 0);
        $settings = new SettingsErrorCatalog();
        if ($settings->isSettingsCode($code)) {
            return $this->explainSettingsCode($code, $settings->find($code));
        }

        $constant = $this->constantName(ErrorCodes::class, 'ERR_', $code);
        if ($constant === null) {
            $this->throwUnknown($code);
        }
        $entry = (new RestErrorCatalog())->find($code);

        return $this->result(
            $code,
            self::KIND_REST,
            $constant,
            $this->httpStatus($code),
            $entry !== null ? $entry[0] : __('No specific guidance for this code; read the error message.', 'simple-jwt-login'),
            $entry !== null ? $entry[1] : []
        );
    }

    /**
     * @param int        $code
     * @param array|null $entry
     * @return array
     * @throws Exception
     */
    private function explainSettingsCode($code, $entry)
    {
        if ($entry === null) {
            $this->throwUnknown($code);
        }
        $section = $this->constantName(
            SettingsErrors::class,
            'PREFIX_',
            (int) floor($code / SettingsErrors::PREFIX_LEEWAY)
        );

        return $this->result($code, self::KIND_SETTINGS, $section, null, $entry[0], $entry[1]);
    }

    /**
     * @param int         $code
     * @param string      $kind
     * @param string|null $constant
     * @param int|null    $httpStatus
     * @param string      $explanation
     * @param string[]    $paths
     * @return array
     */
    private function result($code, $kind, $constant, $httpStatus, $explanation, array $paths)
    {
        return [
            'error_code'       => $code,
            'kind'             => $kind,
            'constant'         => $constant,
            'http_status'      => $httpStatus,
            'explanation'      => $explanation,
            'related_settings' => array_values($paths),
            'settings'         => (object) $this->settingValues($paths),
        ];
    }

    /**
     * Current, redacted values of the settings behind an error. Empty for
     * non-administrators: the site configuration is not theirs to read.
     *
     * @param string[] $paths
     * @return array
     */
    private function settingValues(array $paths)
    {
        if (!$this->isAdministrator()) {
            return [];
        }

        $redacted = (new SettingsRedactor($this->policy))->redact($this->jwtSettings->getStoredSettings());
        $values   = [];
        foreach ($paths as $path) {
            $values[$path] = SettingsPath::get($redacted, $path);
        }

        return $values;
    }

    /**
     * @param int $code
     * @return int
     */
    private function httpStatus($code)
    {
        if ($code <= RestErrorCatalog::LAST_JWT_LIBRARY_CODE) {
            return 400;
        }

        return StatusCodeHelper::getStatusCodeFromException(new Exception('', $code));
    }

    /**
     * @param string $className
     * @param string $prefix
     * @param int    $value
     * @return string|null
     */
    private function constantName($className, $prefix, $value)
    {
        $reflection = new ReflectionClass($className);
        foreach ($reflection->getConstants() as $name => $constantValue) {
            if ($constantValue === $value && strpos($name, $prefix) === 0) {
                return $name;
            }
        }

        return null;
    }


    /**
     * @param int $code
     * @throws Exception
     */
    private function throwUnknown($code)
    {
        throw new Exception(
            esc_html(sprintf(
                // translators: %d = error code
                __('%d is not a Simple JWT Login error code. Pass the error_code field from the error response.', 'simple-jwt-login'),
                $code
            )),
            absint(ErrorCodes::ERR_ABILITY_UNKNOWN_ERROR_CODE)
        );
    }
}
