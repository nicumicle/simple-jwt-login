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

    /**
     * @param string $email
     * @return int
     */
    protected function lookupUserId(string $email): int
    {
        $table   = self::getTablePrefix() . 'users';
        $escaped = self::$dbCon->real_escape_string($email);
        $res     = self::$dbCon->query(
            "SELECT ID FROM `{$table}` WHERE user_email = '{$escaped}' LIMIT 1"
        );
        $userId = 0;
        while ($row = $res->fetch_assoc()) {
            $userId = (int) $row['ID'];
        }
        $this->assertGreaterThan(0, $userId, "User '{$email}' not found in DB");

        return $userId;
    }

    /**
     * @param int $userId
     * @return void
     */
    protected function promoteToAdmin(int $userId): void
    {
        $prefix   = self::getTablePrefix();
        $usermeta = $prefix . 'usermeta';
        $metaKey  = $prefix . 'capabilities';
        $caps     = 'a:1:{s:13:"administrator";b:1;}';

        self::$dbCon->query(
            "UPDATE `{$usermeta}` SET meta_value = '{$caps}'
             WHERE user_id = {$userId} AND meta_key = '{$metaKey}'"
        );
    }

    /**
     * Register a new user, promote them to admin, and return their credentials.
     *
     * @return array{string, string, int}  [email, password, userId]
     */
    protected function createAdminUser(): array
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $userId = $this->lookupUserId($email);
        $this->promoteToAdmin($userId);

        return [$email, $password, $userId];
    }
}
