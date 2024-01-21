<?php

/**
 * @author   Nicu Micle <contact@simplejwtlogin.com>
 * @license  http://opensource.org/licenses/BSD-3-Clause 3-clause BSD
 * @link     https://github.com/nicumicle/simple-jwt-login
 */

namespace SimpleJWTLogin\Libraries;

class ServerCall
{
    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_PUT = 'PUT';
    const REQUEST_METHOD_DELETE = 'DELETE';

    /**
     * @param string $method
     * @param string $url
     * @param array $parameters
     * @param int $statusCode
     * @param null|string $callResult
     * @return array
     */
    public static function call($method, $url, $parameters, &$statusCode, &$callResult = null)
    {
        $args     = array(
            'method' => strtoupper($method),
            'timeout'     => isset($parameters['timeout'])
                ? $parameters['timeout']
                : 10,
            'redirection' => isset($parameters['redirection'])
                ? $parameters['redirection']
                : 10,
        );

        if (isset($parameters['body'])) {
            $args['body'] = $parameters['body'];
        }
        if (isset($parameters['headers'])) {
            $args['headers'] = $parameters['headers'];
        }

        $response = wp_remote_request($url, $args);
        $statusCode = wp_remote_retrieve_response_code($response);
        $callResult = wp_remote_retrieve_body($response);

        return json_decode($callResult, true);
    }

    /**
     * @param string $url
     * @param array $parameters
     * @param int $statusCode
     * @param null|string $callResult
     * @return array
     */
    public static function get($url, $parameters, &$statusCode, &$callResult)
    {
        return self::call(self::REQUEST_METHOD_GET, $url, $parameters, $statusCode, $callResult);
    }

    /**
     * @param string $url
     * @param array $parameters
     * @param int $statusCode
     * @param null|string $callResult
     * @return array
     */
    public static function post($url, $parameters, &$statusCode, &$callResult)
    {
        return self::call(self::REQUEST_METHOD_POST, $url, $parameters, $statusCode, $callResult);
    }

    /**
     * @param string $url
     * @param array $parameters
     * @param int $statusCode
     * @param null|string $callResult
     * @return array
     */
    public static function put($url, $parameters, &$statusCode, &$callResult)
    {
        return self::call(self::REQUEST_METHOD_PUT, $url, $parameters, $statusCode, $callResult);
    }

    /**
     * @param string $url
     * @param array $parameters
     * @param int $statusCode
     * @param null|string $callResult
     * @return array
     */
    public static function delete($url, $parameters, &$statusCode, &$callResult)
    {
        return self::call(self::REQUEST_METHOD_DELETE, $url, $parameters, $statusCode, $callResult);
    }
}
