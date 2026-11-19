<?php

namespace SimpleJWTLogin\Helpers;

class ServerHelper
{
    /**
     * @var array
     */
    private $server;

    /**
     * @var boolean
     */
    private $useProxyHeaders = false;

    /**
     * @param array $server
     */
    public function __construct($server)
    {
        $this->server = $server;
    }

    /**
     * Named constructor for sites behind a trusted reverse proxy:
     * the right-most X-Forwarded-For hop is used to detect the client IP.
     *
     * @param array $server
     * @return ServerHelper
     */
    public static function withTrustedProxyHeaders($server)
    {
        $serverHelper = new self($server);
        $serverHelper->useProxyHeaders = true;

        return $serverHelper;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        $allHeader = $this->getAllHeaders();
        if (!empty($allHeader)) {
            return $allHeader;
        }

        $headers = [];
        foreach ($this->server as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $key = str_replace(
                    ' ',
                    '-',
                    ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                );
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * Read a single request header by name (case-insensitive) without building
     * the full header map. Used on the hot path where only one header matters.
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader($name)
    {
        $allHeaders = $this->getAllHeaders();
        if (!empty($allHeaders)) {
            $lowerName = strtolower($name);
            foreach ($allHeaders as $key => $value) {
                if (strtolower($key) === $lowerName) {
                    return $value;
                }
            }
            return null;
        }

        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($this->server[$serverKey]) ? $this->server[$serverKey] : null;
    }

    /**
     * Whether the site runs behind a trusted reverse proxy, so proxy headers
     * (X-Forwarded-For, X-Forwarded-Proto, ...) may be used.
     *
     * @return bool
     */
    public function usesTrustedProxyHeaders()
    {
        return $this->useProxyHeaders;
    }

    /**
     * @return string|null
     */
    public function getClientIP()
    {
        if ($this->useProxyHeaders) {
            $proxyClientIp = $this->getClientIpFromProxyHeaders();
            if ($proxyClientIp !== null) {
                return $proxyClientIp;
            }
        }
        if (!empty($this->server['REMOTE_ADDR'])) {
            return $this->server['REMOTE_ADDR'];
        }
        return null;
    }

    /**
     * @return string|null
     */
    private function getClientIpFromProxyHeaders()
    {
        // Client-IP is never used: most proxies pass it through from the client untouched.
        if (empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            return null;
        }
        // Only the right-most entry is the hop appended by the trusted proxy; the rest come from the client.
        $forwardedIps = explode(',', (string) $this->server['HTTP_X_FORWARDED_FOR']);
        $proxyHop     = trim(end($forwardedIps));

        return filter_var($proxyHop, FILTER_VALIDATE_IP) !== false ? $proxyHop : null;
    }

    /**
     * @param string $ipList
     * @return bool
     */
    public function isClientIpInList($ipList)
    {
        $clientIp = $this->getClientIP();
        foreach (explode(',', $ipList) as $ip) {
            if ($clientIp === trim($ip)) {
                return true;
            }
            if (strpos($ip, '*') !== false) {
                if ($clientIp === null) {
                    continue;
                }
                $clientIpParts = explode('.', $clientIp);
                $ipParts = explode('.', trim($ip));
                $equalParts = 0;
                foreach ($clientIpParts as $key => $ipPart) {
                    if (!isset($ipParts[$key])) {
                        break;
                    }
                    if ($ipPart === $ipParts[$key] || $ipParts[$key] === '*') {
                        $equalParts++;
                    }
                }
                if ($equalParts === 4) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    private function getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        return [];
    }

    /**
     * @return string|null
     */
    public function getRequestMethod()
    {
        return isset($this->server['REQUEST_METHOD'])
            ? $this->server['REQUEST_METHOD']
            : null;
    }

    /**
     * The method the REST API dispatches: WP_REST_Server::serve_request() replaces
     * REQUEST_METHOD with ?_method= or the X-HTTP-Method-Override header, for any
     * verb, before authentication runs. Authorize by this method, never by
     * REQUEST_METHOD alone.
     *
     * @return string|null
     */
    public function getRestRequestMethod()
    {
        $query = [];
        parse_str(isset($this->server['QUERY_STRING']) ? (string) $this->server['QUERY_STRING'] : '', $query);
        if (isset($query['_method'])) {
            return is_string($query['_method']) ? strtoupper($query['_method']) : '';
        }
        if (isset($this->server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper((string) $this->server['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }

        return $this->getRequestMethod();
    }

    /**
     * @return bool
     */
    public function isHttps()
    {
        $https = isset($this->server['HTTPS']) ? strtolower((string) $this->server['HTTPS']) : '';
        if ($https === 'on' || $https === '1') {
            return true;
        }

        return isset($this->server['SERVER_PORT']) && (string) $this->server['SERVER_PORT'] === '443';
    }

    /**
     * @return string
     */
    public function getCurrentURL()
    {
        return 'http' . (isset($this->server['HTTPS']) && $this->server['HTTPS'] === 'on' ? 's' : '')
            . '://' . $this->server['HTTP_HOST']
            . $this->server['REQUEST_URI'];
    }
}
