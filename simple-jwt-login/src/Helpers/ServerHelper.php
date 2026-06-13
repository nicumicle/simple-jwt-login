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
     * the Client-IP / X-Forwarded-For headers are used to detect the client IP.
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
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return trim($this->server['HTTP_CLIENT_IP']);
        }
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            // The right-most entry is the hop appended by the trusted proxy
            $forwardedIps = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
            return trim(end($forwardedIps));
        }
        return null;
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
     * @return string
     */
    public function getCurrentURL()
    {
        return 'http' . (isset($this->server['HTTPS']) && $this->server['HTTPS'] === 'on' ? 's' : '')
            . '://' . $this->server['HTTP_HOST']
            . $this->server['REQUEST_URI'];
    }
}
