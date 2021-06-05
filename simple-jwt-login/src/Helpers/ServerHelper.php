<?php
namespace SimpleJWTLogin\Helpers;

class ServerHelper
{
    /**
     * @var array
     */
    private $server;

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
            if (substr($name, 0, 5) == 'HTTP_') {
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
        $clientIp = null;
        if (!empty($this->server['HTTP_CLIENT_IP'])) {   //check ip from share internet
            $clientIp = $this->server['HTTP_CLIENT_IP'];
        } elseif (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
            $clientIp = $this->server['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($this->server['REMOTE_ADDR'])) {
            $clientIp = $this->server['REMOTE_ADDR'];
        }

        return $clientIp;
    }

    /**
     * @param string $ipList
     * @return bool
     */
    public function isClientIpInList($ipList)
    {
        return in_array(
            $this->getClientIP(),
            array_map(
                'trim',
                explode(',', $ipList)
            )
        );
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
}
