<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Modules\Abilities\AbstractAbility;
use SimpleJWTLogin\Modules\Jwt\JwtWrapper;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Services\BaseService;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;

/**
 * Runs a JWT through the same key / rule / algorithm selection the plugin
 * endpoints use and reports where it fails: format, signature, expiry, the
 * claim that identifies the user, or the user lookup. The signing key is
 * never returned; the token itself is not echoed back.
 */
class InspectJwtAbility extends AbstractAbility
{
    /**
     * @var string[]
     */
    private static $loginByNames = array(
        LoginSettings::JWT_LOGIN_BY_EMAIL             => 'email',
        LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID => 'wordpress_user_id',
        LoginSettings::JWT_LOGIN_BY_USER_LOGIN        => 'user_login',
    );

    /**
     * @var RevokedTokenRepositoryInterface
     */
    private $revokedTokens;

    /**
     * @param SimpleJWTLoginSettings          $jwtSettings
     * @param RevokedTokenRepositoryInterface $revokedTokens
     */
    public function __construct(
        SimpleJWTLoginSettings $jwtSettings,
        RevokedTokenRepositoryInterface $revokedTokens
    ) {
        parent::__construct($jwtSettings);
        $this->revokedTokens = $revokedTokens;
    }

    protected function getSlug()
    {
        return 'inspect-jwt';
    }

    public function isReadOnly()
    {
        return true;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Inspect a JWT against Simple JWT Login', 'simple-jwt-login'),
            'description'   => __(
                'Decodes a JWT and checks it like the plugin endpoints do: which JWT rule matches, the expected algorithm, signature verification with the configured key, expiry, two-factor interim tokens, the claim used to find the WordPress user, whether that user exists and whether the token was revoked. Use it when a client gets a JWT error. Tokens issued by Google or Auth0 are verified by the provider at request time and are only decoded here. The signing key is never returned. Pass a token the user shared on purpose; anyone holding a valid JWT can act as that user.',
                'simple-jwt-login'
            ),
            'input_schema'  => [
                'type'                 => 'object',
                'properties'           => [
                    'jwt' => ['type' => 'string', 'minLength' => 1],
                ],
                'required'             => ['jwt'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'valid'              => ['type' => 'boolean'],
                    'stage'              => ['type' => 'string'],
                    'error_code'         => ['type' => ['integer', 'null']],
                    'error'              => ['type' => ['string', 'null']],
                    'header'             => ['type' => ['object', 'null']],
                    'payload'            => ['type' => ['object', 'null']],
                    'matched_rule'       => ['type' => ['object', 'null']],
                    'expected_algorithm' => ['type' => ['string', 'null']],
                    'expires_in'         => ['type' => ['integer', 'null']],
                    'user'               => ['type' => ['object', 'null']],
                ],
            ],
            'annotations'   => $this->annotations(true, false, true),
        ];
    }

    public function execute(array $input)
    {
        $jwt    = trim((string) $this->inputValue($input, 'jwt', ''));
        $jwt    = preg_replace('/^Bearer\s+/i', '', $jwt);
        $report = $this->emptyReport();

        try {
            $parts = (new JwtWrapper())->extractDataFromJwt($jwt);
        } catch (Exception $exception) {
            return $this->fail($report, 'decode', $exception);
        }
        $report['header']  = (object) (array) $parts['header'];
        $report['payload'] = (object) (array) $parts['payload'];
        $payload           = (array) $parts['payload'];
        if (isset($payload['exp']) && is_numeric($payload['exp'])) {
            $report['expires_in'] = (int) $payload['exp'] - time();
        }

        $externalIssuer = $this->externalIssuer($payload);
        if ($externalIssuer !== null) {
            $report['stage'] = 'external_issuer';
            $report['error'] = sprintf(
                // translators: %s = provider name (Google, Auth0)
                __('Issued by %s: the signature is verified by the provider when the token is used.', 'simple-jwt-login'),
                $externalIssuer
            );

            return $report;
        }

        return $this->verifyAndFindUser($report, $jwt, $parts);
    }

    /**
     * @param array  $report
     * @param string $jwt
     * @param array  $parts
     * @return array
     */
    private function verifyAndFindUser(array $report, $jwt, array $parts)
    {
        $rule      = $this->jwtSettings->getJwtRulesSettings()->findMatchingRuleConfig($parts);
        $algorithm = $rule !== null
            ? (string) $rule['algorithm']
            : $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm();
        $report['matched_rule']       = $this->describeRule($rule);
        $report['expected_algorithm'] = $algorithm;

        try {
            $wrapper = new JwtWrapper();
            $wrapper->applyLeeway(BaseService::JWT_LEEWAY);
            $key     = JwtKeyFactory::getFactoryFromConfig($this->jwtSettings, $rule)->getPublicKey();
            $decoded = (array) $wrapper->decode($jwt, $key, [$algorithm]);
        } catch (Exception $exception) {
            return $this->fail($report, 'signature', $exception);
        }

        if (!empty($decoded['tfa_pending'])) {
            $report['stage']      = 'two_factor_pending';
            $report['error_code'] = ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REJECTED;
            $report['error']      = __('This is an interim two-factor JWT: complete POST /auth/2fa to get a usable JWT.', 'simple-jwt-login');

            return $report;
        }

        $report['user'] = $this->findUser($decoded);
        if (!$report['user']['found']) {
            $report['stage'] = 'user';
            $report['error'] = __('The JWT is valid but no WordPress user matches the configured claim.', 'simple-jwt-login');

            return $report;
        }
        if ($this->revokedTokens->existsForUser($report['user']['id'], hash('sha256', $jwt))) {
            $report['stage']      = 'revoked';
            $report['error_code'] = ErrorCodes::ERR_REVOKED_TOKEN;
            $report['error']      = __('This JWT was revoked.', 'simple-jwt-login');

            return $report;
        }
        $report['valid'] = true;
        $report['stage'] = 'ok';

        return $report;
    }

    /**
     * @param array $payload
     * @return array
     */
    private function findUser(array $payload)
    {
        $loginSettings = $this->jwtSettings->getLoginSettings();
        $loginBy       = (int) $loginSettings->getJWTLoginBy();
        $claim         = (string) $loginSettings->getJwtLoginByParameter();
        $value         = $this->claimValue($payload, $claim);
        $user          = [
            'lookup_by' => isset(self::$loginByNames[$loginBy]) ? self::$loginByNames[$loginBy] : (string) $loginBy,
            'claim'     => $claim,
            'value'     => $value,
            'found'     => false,
        ];
        if ($value === null) {
            return $user;
        }

        $wordPressData = $this->jwtSettings->getWordPressData();
        $wpUser        = $this->lookupUser($loginBy, $value);
        if (!$wordPressData->isInstanceOfuser($wpUser)) {
            return $user;
        }

        return array_merge($user, [
            'found'      => true,
            'id'         => (int) $wordPressData->getUserProperty($wpUser, 'ID'),
            'user_login' => (string) $wordPressData->getUserProperty($wpUser, 'user_login'),
            'roles'      => array_values((array) $wordPressData->getUserRoles($wpUser)),
        ]);
    }

    /**
     * @param int    $loginBy
     * @param string $value
     * @return mixed
     */
    private function lookupUser($loginBy, $value)
    {
        $wordPressData = $this->jwtSettings->getWordPressData();
        if ($loginBy === LoginSettings::JWT_LOGIN_BY_EMAIL) {
            return $wordPressData->getUserDetailsByEmail($value);
        }
        if ($loginBy === LoginSettings::JWT_LOGIN_BY_USER_LOGIN) {
            return $wordPressData->getUserByUserLogin($value);
        }

        return $wordPressData->getUserDetailsById((int) $value);
    }

    /**
     * Read a claim, supporting dot notation for nested claims (e.g. "data.user.id").
     *
     * @param array  $payload
     * @param string $claim
     * @return string|null
     */
    private function claimValue(array $payload, $claim)
    {
        $node = $payload;
        foreach (explode('.', $claim) as $segment) {
            $node = (array) $node;
            if ($segment === '' || !isset($node[$segment])) {
                return null;
            }
            $node = $node[$segment];
        }

        return is_scalar($node) ? (string) $node : null;
    }

    /**
     * @param array|null $rule
     * @return object|null
     */
    private function describeRule($rule)
    {
        if ($rule === null) {
            return null;
        }
        $index = array_search($rule, $this->jwtSettings->getJwtRulesSettings()->getRules(), true);

        return (object) [
            'index'              => $index === false ? null : (int) $index,
            'condition_type'     => isset($rule['condition_type']) ? $rule['condition_type'] : 'iss',
            'condition_key'      => isset($rule['condition_key']) ? $rule['condition_key'] : 'iss',
            'condition_operator' => isset($rule['condition_operator']) ? $rule['condition_operator'] : 'equals',
            'condition_value'    => isset($rule['condition_value']) ? $rule['condition_value'] : '',
            'algorithm'          => isset($rule['algorithm']) ? $rule['algorithm'] : '',
        ];
    }

    /**
     * Mirrors the issuers BaseService hands over to an OAuth provider.
     *
     * @param array $payload
     * @return string|null
     */
    private function externalIssuer(array $payload)
    {
        $iss          = isset($payload['iss']) ? (string) $payload['iss'] : '';
        $integrations = $this->jwtSettings->getIntegrationsSettings();
        $google       = $integrations->google();
        if ($iss === GoogleOauth::IIS && $google->isEnabled() && $google->isAllowedOnAllEndpoints()) {
            return 'Google';
        }
        $auth0 = $integrations->auth0();
        if ($auth0->isEnabled() && $auth0->getDomain() !== '' && $iss === $auth0->getDomain()
            && $auth0->isAllowedOnAllEndpoints()
        ) {
            return 'Auth0';
        }

        return null;
    }

    /**
     * @return array
     */
    private function emptyReport()
    {
        return [
            'valid'              => false,
            'stage'              => '',
            'error_code'         => null,
            'error'              => null,
            'header'             => null,
            'payload'            => null,
            'matched_rule'       => null,
            'expected_algorithm' => null,
            'expires_in'         => null,
            'user'               => null,
        ];
    }

    /**
     * @param array     $report
     * @param string    $stage
     * @param Exception $exception
     * @return array
     */
    private function fail(array $report, $stage, Exception $exception)
    {
        $report['stage']      = $stage;
        $report['error_code'] = (int) $exception->getCode();
        $report['error']      = html_entity_decode($exception->getMessage(), ENT_QUOTES);

        return $report;
    }
}
