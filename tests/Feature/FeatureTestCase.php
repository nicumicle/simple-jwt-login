<?php

namespace SimpleJwtLoginTests\Feature;

use Faker\Factory;

/**
 * Clean REST-style test base that mirrors the WP_REST_Request / rest_do_request API.
 *
 * Bridges to Guzzle internally because this suite runs against a live HTTP
 * endpoint (see phpunit-feature.xml.dist). The WP-native equivalent lives in
 * tests/WP/WPTestCase.php and uses WP_UnitTestCase + real WP_REST_Request.
 */
class FeatureTestCase extends TestBase
{
    /**
     * @param string               $method
     * @param string               $route
     * @param array<string,mixed>  $params
     * @param array<string,string> $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function request(string $method, string $route, array $params = [], array $headers = [])
    {
        $uri     = self::API_URL . '?rest_route=' . $route;
        $options = ['http_errors' => false];

        if (!empty($params)) {
            $options['body'] = json_encode($params);
        }

        if (!empty($headers)) {
            $options['headers'] = $headers;
        }

        return $this->client->request($method, $uri, $options);
    }

    /**
     * @param string               $method
     * @param string               $route
     * @param array<string,mixed>  $body
     * @param array<string,string> $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function jsonRequest(string $method, string $route, array $body = [], array $headers = [])
    {
        $uri     = self::API_URL . '?rest_route=' . $route;
        $options = [
            'http_errors' => false,
            'headers'     => array_merge(['Content-Type' => 'application/json'], $headers),
        ];

        if (!empty($body)) {
            $options['body'] = json_encode($body);
        }

        return $this->client->request($method, $uri, $options);
    }

    /**
     * @param array<string,string> $args  Override email, user_login, or password.
     * @return array{string, string, int}  [email, password, http_status]
     */
    protected function createUser(array $args = []): array
    {
        $faker    = Factory::create();
        $email    = $args['user_email'] ?? ($faker->randomNumber(6) . $faker->email());
        $password = $args['user_pass']  ?? '1234';
        $login    = $args['user_login'] ?? md5($email);

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
            'email'      => $email,
            'user_login' => $login,
            'password'   => $password,
        ]);

        return [$email, $password, $response->getStatusCode()];
    }

    /**
     * @param string $token
     * @return array<string,string>
     */
    protected function authHeader(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }
}
