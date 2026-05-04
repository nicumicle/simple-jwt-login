<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;

interface ServiceInterface
{
    /**
     * @param string $requestMethod
     * @return $this
     */
    public function withRequestMethod($requestMethod);

    /**
     * @param SimpleJWTLoginSettings $settings
     * @return $this
     */
    public function withSettings(SimpleJWTLoginSettings $settings);

    /**
     * @param array $request
     * @return $this
     */
    public function withRequest($request);

    /**
     * @param array $session
     * @return $this
     */
    public function withSession($session);

    /**
     * @param array $cookies
     * @return $this
     */
    public function withCookies($cookies);

    /**
     * @param ServerHelper $serverHelper
     * @return $this
     */
    public function withServerHelper(ServerHelper $serverHelper);

    /**
     * @param RefreshTokenRepositoryInterface $repository
     * @return $this
     */
    public function withRefreshTokenRepository(RefreshTokenRepositoryInterface $repository);

    /**
     * @param WebhookLogRepositoryInterface $repository
     * @return $this
     */
    public function withWebhookLogRepository(WebhookLogRepositoryInterface $repository);

    /**
     * @return mixed
     * @throws Exception
     */
    public function makeAction();
}
