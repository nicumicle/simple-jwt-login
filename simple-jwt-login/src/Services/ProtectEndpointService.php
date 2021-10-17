<?php

declare(strict_types=1);

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
        $action = $this->jwtSettings->getProtectEndpointsSettings()->getAction();

        if (strpos(ltrim($endpoint, '/'), $this->jwtSettings->getGeneralSettings()->getRouteNamespace()) === 0) {
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
                    if (empty($whitelistedDomain)) {
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
                    if (empty($protectedEndpoint)) {
                        continue;
                    }
                    $protectedEndpoint = $this->removeWpJsonFromEndpoint($protectedEndpoint);
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
        return str_replace('/wp-json', '', $endpoint);
    }
}
