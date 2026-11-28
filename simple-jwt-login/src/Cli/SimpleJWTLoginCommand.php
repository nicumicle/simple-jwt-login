<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Services\RefreshTokenService;

/**
 * Manages Simple JWT Login via WP-CLI.
 */
class SimpleJWTLoginCommand extends AbstractCommand
{
    /**
     * Generate a JWT token by authenticating with username/email and password.
     *
     * ## OPTIONS
     *
     * [--username=<username>]
     * : WordPress username.
     *
     * [--email=<email>]
     * : WordPress user email address.
     *
     * [--login=<login>]
     * : WordPress username or email address (tried as username first, then email).
     *
     * [--password=<password>]
     * : WordPress user password. Leave it out to type it (hidden) or pipe it on STDIN, so it is
     * not saved in the shell history.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt login --username=admin
     *     wp jwt login --email=admin@example.com --format=json < password.txt
     *     wp jwt login --login=admin --password=secret
     *
     * @subcommand login
     *
     * @param array $args Positional arguments (none accepted).
     * @param array $assocArgs Named arguments.
     * @return void
     * @throws Exception
     */
    public function login(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $format = isset($assocArgs['format']) ? $assocArgs['format'] : 'text';

        if (!in_array($format, array('text', 'json'), true)) {
            \WP_CLI::error(__('Invalid format. Accepted values: text, json', 'simple-jwt-login'));
            return;
        }

        try {
            $request = $this->buildRequest($assocArgs);
            $identifier = array_diff_key($request, array('password' => true));
            $jwt = $this->audited('login', function () use ($request) {
                return $this->generateToken($request);
            }, $identifier);
            $this->outputResult($jwt, $format);
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
        }
    }

    /**
     * Decode a JWT token payload without verifying the signature.
     *
     * ## OPTIONS
     *
     * <token>
     * : The JWT token to decode.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt decode eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MX0.abc
     *     wp jwt decode eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MX0.abc --format=json
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Named arguments.
     * @return void
     */
    public function decode(array $args, array $assocArgs)
    {
        if (empty($args)) {
            \WP_CLI::error(__('Please provide a JWT token: wp jwt decode <token>', 'simple-jwt-login'));
            return;
        }

        $token = $args[0];
        $format = $this->getFormat($assocArgs);

        $payload = $this->decodeJwtPayload($token);
        if ($payload === null) {
            \WP_CLI::error(
                __(
                    'Could not decode token. Ensure it has a valid JWT structure (header.payload.signature).',
                    'simple-jwt-login'
                )
            );
            return;
        }

        \WP_CLI::warning(
            __(
                'The token signature was NOT verified. Use `wp jwt validate` to verify authenticity.',
                'simple-jwt-login'
            )
        );
        $this->outputKeyValue($payload, $format);
    }

    /**
     * Validate a JWT token - verifies signature, expiry, user and revocation.
     *
     * Exits with 1 when the token is not valid.
     *
     * ## OPTIONS
     *
     * <token>
     * : The JWT token to validate.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt validate eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MX0.abc
     *     wp jwt validate eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MX0.abc --format=json
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Named arguments.
     * @return void
     */
    public function validate(array $args, array $assocArgs)
    {
        if (empty($args)) {
            \WP_CLI::error(__('Please provide a JWT token: wp jwt validate <token>', 'simple-jwt-login'));
            return;
        }

        $token = $args[0];
        $format = $this->getFormat($assocArgs);

        try {
            $this->doValidateToken($token);
            $result = array('valid' => true, 'message' => __('Token is valid.', 'simple-jwt-login'));
        } catch (Exception $exception) {
            $result = array('valid' => false, 'message' => $exception->getMessage());
        }

        $this->outputKeyValue($result, $format);
        if (!$result['valid']) {
            $this->halt(1);
        }
    }

    /**
     * Revoke a JWT token so it can no longer be used.
     *
     * The token signature is verified before revocation. Requires the plugin's
     * authentication and decryption settings to be configured.
     *
     * ## OPTIONS
     *
     * <token>
     * : The JWT token to revoke.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt revoke eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MX0.abc
     *     wp jwt revoke eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MX0.abc --format=json
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Named arguments.
     * @return void
     */
    public function revoke(array $args, array $assocArgs)
    {
        if (empty($args)) {
            \WP_CLI::error(__('Please provide a JWT token: wp jwt revoke <token>', 'simple-jwt-login'));
            return;
        }

        $token = $args[0];
        $format = $this->getFormat($assocArgs);

        try {
            $this->audited('revoke', function () use ($token) {
                $this->doRevokeToken($token);
            });
            $result = array('success' => true, 'message' => __('Token has been revoked.', 'simple-jwt-login'));
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        $this->outputKeyValue($result, $format);
    }

    /**
     * Issue a JWT for a user, without their password.
     *
     * Uses the same claims and signing key as /auth. Two-factor authentication is not asked.
     * A refresh token is printed too when refresh tokens are enabled.
     *
     * ## OPTIONS
     *
     * <user>
     * : User ID, login or email.
     *
     * [--ttl=<minutes>]
     * : Expiration in minutes. Default: the configured JWT expiration.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt generate admin
     *     wp jwt generate 42 --ttl=10 --format=json
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function generate(array $args, array $assocArgs)
    {
        $format = $this->getFormat($assocArgs);
        $ttl = isset($assocArgs['ttl']) ? $assocArgs['ttl'] : null;
        if ($ttl !== null && (!ctype_digit((string) $ttl) || (int) $ttl < 1)) {
            \WP_CLI::error(__('--ttl must be a number of minutes, at least 1.', 'simple-jwt-login'));
            return;
        }

        try {
            $user = $this->findUser(isset($args[0]) ? $args[0] : '');
            $userId = (int) $this->createWordPressData()->getUserProperty($user, 'ID');
            $tokens = $this->audited('generate', function () use ($user, $ttl) {
                return $this->createTokenService()->issueToken($user, $ttl === null ? null : (int) $ttl);
            }, array('user_id' => $userId));
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        if ($format === self::FORMAT_TEXT && !isset($tokens['refresh_token'])) {
            \WP_CLI::line($tokens['jwt']);
            return;
        }
        $this->outputKeyValue($tokens, $format);
    }

    /**
     * Exchange a refresh token for a new JWT and refresh token, like the /auth/refresh endpoint.
     *
     * The endpoint checks apply: refresh tokens enabled, allowed IPs and the auth code when required.
     *
     * ## OPTIONS
     *
     * <refresh-token>
     * : The refresh token.
     *
     * [--auth-code=<code>]
     * : Auth code, when the refresh endpoint requires one.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt refresh 3f2a...c9 --format=json
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function refresh(array $args, array $assocArgs)
    {
        if (empty($args)) {
            \WP_CLI::error(__('Please provide a refresh token: wp jwt refresh <refresh-token>', 'simple-jwt-login'));
            return;
        }
        $format = $this->getFormat($assocArgs);
        $request = array('refresh_token' => $args[0]);

        try {
            $jwtSettings = $this->createSettings();
            if (isset($assocArgs['auth-code'])) {
                $request[$jwtSettings->getAuthCodesSettings()->getAuthCodeKey()] = $assocArgs['auth-code'];
            }
            $data = $this->audited('refresh', function () use ($request, $jwtSettings) {
                return $this->doRefresh($request, $jwtSettings);
            });
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        $this->outputKeyValue($data, $format);
    }

    /**
     * Revoke every JWT and refresh token of a user, issued until now.
     *
     * Works whether or not "Revoke tokens on password change" is enabled. Tokens issued afterwards
     * (a new login) are valid.
     *
     * ## OPTIONS
     *
     * <user>
     * : User ID, login or email.
     *
     * [--yes]
     * : Do not ask for confirmation.
     *
     * ## EXAMPLES
     *
     *     wp jwt revoke-all admin --yes
     *
     * @subcommand revoke-all
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function revokeAll(array $args, array $assocArgs)
    {
        try {
            $user = $this->findUser(isset($args[0]) ? $args[0] : '');
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }
        $wordPressData = $this->createWordPressData();
        $userId = (int) $wordPressData->getUserProperty($user, 'ID');
        $this->confirmAction(sprintf(
            /* translators: %s is the user login */
            __('Revoke every token of %s?', 'simple-jwt-login'),
            (string) $wordPressData->getUserProperty($user, 'user_login')
        ), $assocArgs);

        try {
            $this->audited('revoke-all', function () use ($userId) {
                $this->createTokenService()->revokeAll($userId);
            }, array('user_id' => $userId));
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }
        \WP_CLI::success(__('All tokens of the user have been revoked.', 'simple-jwt-login'));
    }

    // -------------------------------------------------------------------------
    // login helpers
    // -------------------------------------------------------------------------

    /**
     * Build request array from CLI arguments.
     *
     * @param array $assocArgs Named CLI arguments.
     * @return array
     * @throws Exception
     */
    private function buildRequest(array $assocArgs)
    {
        $request = array();

        if (!isset($assocArgs['username']) && !isset($assocArgs['email']) && !isset($assocArgs['login'])) {
            throw new Exception(esc_html__('Please provide --username, --email, or --login', 'simple-jwt-login'));
        }
        if (isset($assocArgs['username'])) {
            $request['username'] = $assocArgs['username'];
        } elseif (isset($assocArgs['email'])) {
            $request['email'] = $assocArgs['email'];
        } elseif (isset($assocArgs['login'])) {
            $request['login'] = $assocArgs['login'];
        }

        $request['password'] = isset($assocArgs['password']) ? $assocArgs['password'] : $this->readPassword();
        if ($request['password'] === '') {
            throw new Exception(esc_html__('Please provide --password', 'simple-jwt-login'));
        }

        return $request;
    }

    /**
     * Hidden prompt on a terminal, else the first line of STDIN.
     *
     * @return string
     */
    protected function readPassword()
    {
        if (function_exists('posix_isatty') && posix_isatty(STDIN)) {
            return (string) \cli\prompt(__('Password', 'simple-jwt-login'), false, ': ', true);
        }

        return rtrim((string) fgets(STDIN), "\r\n");
    }

    /**
     * Authenticate user and return a JWT token.
     *
     * @param array $request Authentication request parameters.
     * @return string JWT token string.
     * @throws Exception
     */
    protected function generateToken(array $request)
    {
        $jwtSettings = $this->createSettings();

        // authenticateUser() skips this check.
        if (!$jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled()) {
            throw new Exception(esc_html__('Authentication is not enabled.', 'simple-jwt-login'));
        }

        $response = $this->createAuthenticateService()
            ->withRequest($request)
            ->withCookies(array())
            ->withServerHelper(new ServerHelper(array()))
            ->withSettings($jwtSettings)
            ->authenticateUser();

        $data = $response->get_data();

        if (!empty($data['data']['two_factor_required'])) {
            throw new Exception(
                esc_html__('This user must complete two-factor authentication.', 'simple-jwt-login')
            );
        }

        return $data['data']['jwt'];
    }

    /**
     * Output the JWT token in the requested format.
     *
     * @param string $jwt JWT token string.
     * @param string $format Output format: 'text' or 'json'.
     * @return void
     */
    private function outputResult($jwt, $format)
    {
        if ($format === 'json') {
            \WP_CLI::line((string) json_encode(array('jwt' => $jwt)));
            return;
        }
        \WP_CLI::line($jwt);
    }

    // -------------------------------------------------------------------------
    // Token operation implementations (protected for testability)
    // -------------------------------------------------------------------------

    /**
     * Decode the payload of a JWT without verifying the signature.
     *
     * @param string $jwt
     * @return array|null Null when the JWT structure is invalid.
     */
    protected function decodeJwtPayload($jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(
            (string) base64_decode(strtr($parts[1], '-_', '+/')),
            true
        );

        return is_array($payload) ? $payload : null;
    }

    /**
     * Validate a JWT with the checks of the installed Simple JWT Login version.
     *
     * @param string $jwt
     * @return void
     * @throws Exception When the token is invalid, expired or revoked.
     */
    protected function doValidateToken($jwt)
    {
        $this->createTokenService()->validateToken($jwt);
    }

    /**
     * Revoke a JWT. Its signature is verified first.
     *
     * @param string $jwt
     * @return void
     * @throws Exception When the token is invalid, the user is not found, or the token was already revoked.
     */
    protected function doRevokeToken($jwt)
    {
        $this->createTokenService()->revokeToken($jwt);
    }

    /**
     * @param array                  $request
     * @param SimpleJWTLoginSettings $jwtSettings
     * @return array{jwt: string, refresh_token: string}
     * @throws Exception
     */
    protected function doRefresh(array $request, SimpleJWTLoginSettings $jwtSettings)
    {
        global $wpdb;

        $service = new RefreshTokenService();
        $service->withRefreshTokenRepository(new RefreshTokenRepository($wpdb))
            ->withRevokedTokenRepository(new RevokedTokenRepository($wpdb))
            ->withRequest($request)
            ->withCookies(array())
            ->withServerHelper(new ServerHelper(array()))
            ->withSettings($jwtSettings);
        $data = $service->makeAction()->get_data();

        return $data['data'];
    }

    // -------------------------------------------------------------------------
    // WordPress factories (protected so tests can override via subclass)
    // -------------------------------------------------------------------------

    /**
     * Create the authenticate service instance.
     *
     * @return AuthenticateService
     */
    protected function createAuthenticateService()
    {
        global $wpdb;

        $service = new AuthenticateService();
        // Stores a refresh token on login when the setting is enabled.
        $service->withRefreshTokenRepository(new RefreshTokenRepository($wpdb));

        return $service;
    }

    /**
     * @return TokenService
     */
    protected function createTokenService()
    {
        global $wpdb;

        $service = new TokenService();
        $service->withRequest(array())
            ->withCookies(array())
            ->withServerHelper(new ServerHelper(array()))
            ->withSettings($this->createSettings())
            ->withRevokedTokenRepository(new RevokedTokenRepository($wpdb))
            ->withRefreshTokenRepository(new RefreshTokenRepository($wpdb));

        return $service;
    }
}
