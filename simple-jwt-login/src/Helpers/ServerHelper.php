<?php

namespace SimpleJWTLogin\Helpers;

class ServerHelper
{
    /**
     * @var array
     */
    private $server;

    /**
     * @param array $server
     */
    public function __construct($server)
    {
        $this->server = $server;
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
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        }
        if (!empty($this->server['REMOTE_ADDR'])) {
            return $this->server['REMOTE_ADDR'];
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
