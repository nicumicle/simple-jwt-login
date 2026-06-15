<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ApiKeyPermissions;
use SimpleJWTLogin\Middleware\ApiKeyAuthMiddleware;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;

class ProtectEndpointService extends BaseService
{
    /**
     * @var RouteService $routeService
     */
    private $routeService;

    /**
     * @var ApiKeyRepositoryInterface|null
     */
    private $apiKeyRepository;

    /**
     * @param RouteService $routeService
     *
     * @return $this
     */
    public function withRouteService($routeService)
    {
        $this->routeService = $routeService;

        return $this;
    }

    /**
     * @param ApiKeyRepositoryInterface $repository
     *
     * @return $this
     */
    public function withApiKeyRepository($repository)
    {
        $this->apiKeyRepository = $repository;

        return $this;
    }

    /**
     * @param string $currentUrl
     * @param string $documentRoot
     *
     * @throws Exception
     * @return bool
     */
    public function hasAccess($currentUrl, $documentRoot)
    {
        if (!$this->jwtSettings->getProtectEndpointsSettings()->isEnabled()) {
            return true;
        }

        // WordPress Core or some other plugins use the REST API to create pages/posts, etc.
        // Skip protect endpoint validation for these scenarios if the user is already logged in.
        if ($this->wordPressData->isUserLoggedIn()) {
            return true;
        }

        $parsed = wp_parse_url($currentUrl);

        // Initialize $path safely: parse_url() may not include 'path' for some URL forms.
        $basePath  = rtrim(str_replace($documentRoot, '', ABSPATH), '/');
        $path = isset($parsed['path']) ? str_replace($basePath . '/wp-json', '', $parsed['path']) : '';

        $isEndpointsProtected = true;
        if (!empty(trim($path, '/'))) {
            $isEndpointsProtected = $this->isEndpointProtected($path);
        }
        if (!empty($this->request['rest_route'])) {
            $isEndpointsProtected = $this->isEndpointProtected($this->request['rest_route']);
        }
        if (!$isEndpointsProtected) {
            return true;
        }

        try {
            $jwt = $this->getJwtFromRequestHeaderOrCookie();
            if (empty($jwt)) {
                throw new Exception(
                    __('JWT is not present and we can not search for a user.', 'simple-jwt-login'),
                    ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT
                );
            }

            $user = $this->routeService->getUserFromJwt($jwt);
            $this->validateJwtRevoked(
                $this->wordPressData->getUserProperty($user, 'ID'),
                $jwt
            );

            $this->wordPressData->loginUser($user, null);

            return true;
        } catch (Exception $exception) {
            if ($exception->getCode() === ErrorCodes::ERR_REVOKED_TOKEN) {
                throw $exception;
            }
        }

        return $this->tryApiKeyAuth();
    }

    /**
     * @return bool
     */
    private function tryApiKeyAuth()
    {
        if ($this->apiKeyRepository === null) {
            return false;
        }

        if (!$this->jwtSettings->getApiKeysSettings()->isEnabled()) {
            return false;
        }

        $requiredPermission = ApiKeyPermissions::httpMethodToPermission($this->requestMethod);
        if ($requiredPermission === null) {
            return false;
        }

        $headerName = $this->jwtSettings->getApiKeysSettings()->getHeaderName();
        $keyData = (new ApiKeyAuthMiddleware($this->apiKeyRepository))
            ->validate($this->serverHelper, $requiredPermission, $headerName);

        if ($keyData === null) {
            return false;
        }

        $user = $this->wordPressData->getUserDetailsById((int) $keyData['user_id']);
        $this->wordPressData->loginUser($user, null);

        return true;
    }

    /**
     * @param string $endpoint
     * @return bool
     */
    private function isEndpointProtected($endpoint)
    {
        if (strpos($endpoint, '/') !== 0) {
            $endpoint = '/' . $endpoint;
        }

        $action = $this->jwtSettings->getProtectEndpointsSettings()->getAction();
        $skipNamespace = '/' . trim(
            $this->jwtSettings->getGeneralSettings()->getRouteNamespace(),
            '/'
        );
        $endpoint = $this->removeLastSlash($endpoint);
        $adminPath = trim(
            str_replace($this->wordPressData->getSiteUrl(), '', $this->wordPressData->getAdminUrl()),
            '/'
        );
        if (strpos($endpoint, $skipNamespace) === 0
            || strpos(trim($endpoint, '/'), $adminPath) === 0) {
            // Skip simple-jwt-login endpoints and wp-admin.
            return false;
        }

        $protectSettings = $this->jwtSettings->getProtectEndpointsSettings();
        switch ($action) {
            case ProtectEndpointSettings::ALL_ENDPOINTS:
                return $this->parseDomainsAndGetResult(
                    $endpoint,
                    $protectSettings->getWhitelistedDomains(),
                    true,
                    false
                );
            case ProtectEndpointSettings::SPECIFIC_ENDPOINTS:
                return $this->parseDomainsAndGetResult(
                    $endpoint,
                    $protectSettings->getProtectedEndpoints(),
                    false,
                    true
                );
        }

        return true;
    }

    /**
     * @param string $endpoint
     * @param array $domains
     * @param bool $defaultValue
     * @param bool $setValue
     * @return bool
     */
    private function parseDomainsAndGetResult($endpoint, $domains, $defaultValue, $setValue)
    {
        $isEndpointProtected = $defaultValue;
        foreach ($domains as $protectedEndpoint) {
            $protectedURL = $this->removeWpJsonFromEndpoint($protectedEndpoint['url']);
            $endpoint = $this->removeWpJsonFromEndpoint($endpoint);
            if (empty(trim($protectedURL, '/'))) {
                continue;
            }
            // By default, start_with match
            $match = strpos(strtolower($endpoint), strtolower($protectedURL)) === 0;

            if ($protectedEndpoint['match'] === ProtectEndpointSettings::ENDPOINT_MATCH_EXACT) {
                $match = strtolower($endpoint) === strtolower($protectedURL);
            }

            if (!$match) {
                continue;
            }
           
            switch ($protectedEndpoint['method']) {
                case ProtectEndpointSettings::REQUEST_METHOD_ALL:
                    $isEndpointProtected = $setValue; // Same as before.
                    break;
                default:
                    if ($protectedEndpoint['method'] === $this->requestMethod) {
                        $isEndpointProtected = $setValue;
                    }
                    break;
            }
        }

        return $isEndpointProtected;
    }

    /**
     * @param string $endpoint
     * @return string
     */
    private function removeWpJsonFromEndpoint($endpoint)
    {
        $endpoint = str_replace('/wp-json', '', $endpoint);

        return $this->removeLastSlash($endpoint);
    }

    /**
     * @param string $endpoint
     * @return string
     */
    private function addFirstSlash($endpoint)
    {
        if (strpos($endpoint, '/') !== 0) {
            return '/' . $endpoint;
        }

        return $endpoint;
    }

    /**
     * @param string $endpoints
     * @return string
     */
    private function removeLastSlash($endpoints)
    {
        return $this->addFirstSlash(rtrim($endpoints, '/'));
    }
}
