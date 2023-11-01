<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;

class ProtectEndpointService extends BaseService
{
    /**
     * @var RouteService $routeService
     */
    private $routeService;

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
     * @param string $currentUrl
     * @param string $documentRoot
     * @param array $request
     *
     * @throws Exception
     * @return bool
     */
    public function hasAccess($currentUrl, $documentRoot, $request)
    {
        if ($this->jwtSettings->getProtectEndpointsSettings()->isEnabled() === false) {
            return true;
        }

        $parsed = parse_url($currentUrl);

        $path  = rtrim(str_replace($documentRoot, '', ABSPATH), '/');
        $path = str_replace($path . '/wp-json', '', $parsed['path']);

        $isEndpointsProtected = true;
        if (!empty(trim($path, '/'))) {
            $isEndpointsProtected = $this->isEndpointProtected($path);
        }
        if (!empty($request['rest_route'])) {
            $isEndpointsProtected = $this->isEndpointProtected($request['rest_route']);
        }
        if ($isEndpointsProtected === false) {
            return true;
        }

        try {
            $jwt = $this->getJwtFromRequestHeaderOrCookie();

            if (empty($jwt)) {
                if ($this->routeService->wordPressData->isUserLoggedIn()) {
                    return true;
                }

                throw new Exception('JWT is not present and we can not search for a user.');
            }

            $user = $this->routeService->getUserFromJwt($jwt);
            $this->validateJwtRevoked(
                $this->wordPressData->getUserProperty($user, 'ID'),
                $jwt
            );

            $this->routeService->wordPressData->loginUser($user);

            return true;
        } catch (Exception $e) {
            return false;
        }
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
            //Skip simple jwt login endpoints and wp-admin
            return false;
        }

        switch ($action) {
            case ProtectEndpointSettings::ALL_ENDPOINTS:
                $domains = $this->jwtSettings
                    ->getProtectEndpointsSettings()
                    ->getWhitelistedDomains();
                return $this->parseDomainsAndGetResult(
                    $endpoint,
                    $domains,
                    true,
                    false
                );
            case ProtectEndpointSettings::SPECIFIC_ENDPOINTS:
                $domains = $this->jwtSettings
                    ->getProtectEndpointsSettings()
                    ->getProtectedEndpoints();
                return $this->parseDomainsAndGetResult(
                    $endpoint,
                    $domains,
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
            $protectedEndpoint = $this->removeWpJsonFromEndpoint($protectedEndpoint);
            $endpoint = $this->removeWpJsonFromEndpoint($endpoint);
            if (empty(trim($protectedEndpoint, '/'))) {
                continue;
            }
            if (strpos($endpoint, $protectedEndpoint) === 0) {
                $isEndpointProtected = $setValue;
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
