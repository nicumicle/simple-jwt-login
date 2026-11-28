<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Helpers\Jwt\PasswordChangeCutoff;
use SimpleJWTLogin\Services\AuthenticateService;

/**
 * Issues, validates and revokes tokens for WP-CLI.
 *
 * The REST services can not be used: they also check the endpoint settings, IP and auth codes.
 */
class TokenService extends AuthenticateService
{
    /**
     * @param string $jwt
     * @return void
     * @throws Exception When the token is invalid, expired or revoked.
     */
    public function validateToken($jwt)
    {
        $this->validateJwtRevoked($this->getUserId($jwt), $jwt);
    }

    /**
     * @param string $jwt
     * @return void
     * @throws Exception When the token is invalid, was already revoked, or can not be revoked.
     */
    public function revokeToken($jwt)
    {
        $userId = $this->getUserId($jwt);
        $repository = $this->revokedTokenRepo;
        $tokenHash = hash('sha256', $jwt);
        if ($repository->existsForUser($userId, $tokenHash)) {
            throw new Exception(esc_html__('Token has already been revoked.', 'simple-jwt-login'));
        }

        $payload = $this->getPayloadFromJWT($jwt);
        $expiresAt = isset($payload['exp']) ? gmdate('Y-m-d H:i:s', (int) $payload['exp']) : null;
        if (!$repository->insert($userId, $tokenHash, $expiresAt)) {
            throw new Exception(esc_html__('Unable to save the revoked token.', 'simple-jwt-login'));
        }

        // Same as the revoke endpoint: a refresh token must not issue a new JWT.
        $this->tokenRepository->deleteByUserId($userId);
    }

    /**
     * A JWT with the same claims as /auth, without the password (and without 2FA).
     *
     * @param mixed    $user       WP_User
     * @param int|null $ttlMinutes Overrides the configured expiration.
     * @return array{jwt: string, refresh_token?: string}
     * @throws Exception
     */
    public function issueToken($user, $ttlMinutes = null)
    {
        $authSettings = $this->jwtSettings->getAuthenticationSettings();
        $payload = $this->applyPayloadHook(self::generatePayload(array(), $this->wordPressData, $this->jwtSettings, $user));
        if ($ttlMinutes !== null) {
            $payload['exp'] = time() + ((int) $ttlMinutes * 60);
        }

        $customHeaderClaims = $authSettings->getCustomHeaderClaims();
        $result = array('jwt' => $this->getJwtWrapper()->encode(
            $payload,
            JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
            $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm(),
            empty($customHeaderClaims) ? null : $customHeaderClaims
        ));

        if ($authSettings->isRefreshTokenEnabled()) {
            $result['refresh_token'] = $this->generateRefreshToken();
            $this->storeRefreshToken((int) $this->wordPressData->getUserProperty($user, 'ID'), $result['refresh_token']);
        }

        return $result;
    }

    /**
     * Every JWT and refresh token of the user issued until now stops working.
     *
     * @param int $userId
     * @return void
     */
    public function revokeAll($userId)
    {
        PasswordChangeCutoff::markAllRevoked($this->wordPressData, $userId);
        $this->tokenRepository->deleteByUserId($userId);
    }

    /**
     * @param string $jwt
     * @return int
     * @throws Exception
     */
    protected function getUserId($jwt)
    {
        $this->jwt = $jwt;
        $user = $this->getUserFromCurrentJwt();
        if ($user === null) {
            throw new Exception(esc_html__('User not found.', 'simple-jwt-login'));
        }

        return (int) $this->wordPressData->getUserProperty($user, 'ID');
    }
}
