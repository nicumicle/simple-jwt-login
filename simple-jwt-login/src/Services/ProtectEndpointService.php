<?php

namespace SimpleJWTLogin\Services;

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
            $userID = $this->routeService->getUserIdFromJWT($jwt);
            return !empty($userID);
        } catch (\Exception $e) {
            return false;
        }
    }

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

        if (strpos($endpoint, $skipNamespace) === 0) {
            //Skip simple jwt login endpoints
            return false;
        }
        $isEndpointProtected = true;
        switch ($action) {
            case ProtectEndpointSettings::ALL_ENDPOINTS:
                $isEndpointProtected = true;
                $domains = $this->jwtSettings
                    ->getProtectEndpointsSettings()
                    ->getWhitelistedDomains();
                foreach ($domains as $whitelistedDomain) {
                    $whitelistedDomain = $this->removeWpJsonFromEndpoint($whitelistedDomain);
                    if (empty(trim($whitelistedDomain, '/'))) {
                        continue;
                    }
                    if (strpos($endpoint, $whitelistedDomain) === 0) {
                        $isEndpointProtected = false;
                    }
                }
                break;
            case ProtectEndpointSettings::SPECIFIC_ENDPOINTS:
                $isEndpointProtected = false;
                $domains = $this->jwtSettings
                    ->getProtectEndpointsSettings()
                    ->getProtectedEndpoints();
                foreach ($domains as $protectedEndpoint) {
                    $protectedEndpoint = $this->removeWpJsonFromEndpoint($protectedEndpoint);
                    if (empty(trim($protectedEndpoint, '/'))) {
                        continue;
                    }
                    if (strpos($endpoint, $protectedEndpoint) === 0) {
                        $isEndpointProtected = true;
                    }
                }
                break;
        }

        return $isEndpointProtected;
    }

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
