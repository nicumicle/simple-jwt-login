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

        $parsed = $this->wordPressData->parseUrl($currentUrl);

        // Initialize $path safely: parse_url() may not include 'path' for some URL forms.
        $basePath  = rtrim(str_replace($documentRoot, '', ABSPATH), '/');
        $path = isset($parsed['path']) ? str_replace($basePath . '/wp-json', '', $parsed['path']) : '';

        $isEndpointsProtected = true;
        $requiredRoles = [];
        if (!empty(trim($path, '/'))) {
            $endpointInfo = $this->isEndpointProtected($path);
            $isEndpointsProtected = $endpointInfo['protected'];
            $requiredRoles = $endpointInfo['roles'];
        }
        if (!empty($this->request['rest_route'])) {
            $endpointInfo = $this->isEndpointProtected($this->request['rest_route']);
            $isEndpointsProtected = $endpointInfo['protected'];
            $requiredRoles = $endpointInfo['roles'];
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

            $this->wordPressData->setCurrentUser($user);

            if (!empty($requiredRoles)) {
                $userRoles = $this->wordPressData->getUserRoles($user);
                if (empty(array_intersect($userRoles, $requiredRoles))) {
                    throw new Exception(
                        __('You do not have the required role to access this endpoint.', 'simple-jwt-login'),
                        ErrorCodes::ERR_PROTECT_ENDPOINTS_INSUFFICIENT_ROLE
                    );
                }
            }

            return true;
        } catch (Exception $exception) {
            if ($exception->getCode() === ErrorCodes::ERR_REVOKED_TOKEN
                || $exception->getCode() === ErrorCodes::ERR_PROTECT_ENDPOINTS_INSUFFICIENT_ROLE
            ) {
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
        $this->wordPressData->setCurrentUser($user);

        return true;
    }

    /**
     * Evaluates endpoint rules in order; the first matching rule wins.
     * Falls back to the configured default action when no rule matches.
     *
     * @param string $endpoint
     * @return array{protected: bool, roles: array}
     */
    private function isEndpointProtected($endpoint)
    {
        $notProtected = ['protected' => false, 'roles' => []];

        if (strpos($endpoint, '/') !== 0) {
            $endpoint = '/' . $endpoint;
        }

        $protectSettings = $this->jwtSettings->getProtectEndpointsSettings();
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
            return $notProtected;
        }

        $defaultProtected = $protectSettings->getDefaultAction() === ProtectEndpointSettings::DEFAULT_PROTECT_ALL;
        $cleanEndpoint = $this->removeWpJsonFromEndpoint($endpoint);

        foreach ($protectSettings->getRules() as $rule) {
            $ruleUrl = $this->removeWpJsonFromEndpoint($rule['url']);

            if (empty(trim($ruleUrl, '/'))) {
                continue;
            }

            $matched = strpos(strtolower($cleanEndpoint), strtolower($ruleUrl)) === 0;
            if ($rule['match'] === ProtectEndpointSettings::ENDPOINT_MATCH_EXACT) {
                $matched = strtolower($cleanEndpoint) === strtolower($ruleUrl);
            }

            if (!$matched) {
                continue;
            }

            $methodMatches = $rule['method'] === ProtectEndpointSettings::REQUEST_METHOD_ALL
                || $rule['method'] === $this->requestMethod;

            if (!$methodMatches) {
                continue;
            }

            if ($rule['type'] === ProtectEndpointSettings::RULE_TYPE_PUBLIC) {
                return $notProtected;
            }

            $roles = isset($rule['roles']) ? $rule['roles'] : [];
            return ['protected' => true, 'roles' => $roles];
        }

        return ['protected' => $defaultProtected, 'roles' => []];
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
