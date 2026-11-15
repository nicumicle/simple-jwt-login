<?php

namespace SimpleJwtLoginTests\Feature\Security;

use Psr\Http\Message\ResponseInterface;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Shared helpers for the CSRF / ambient-credential feature tests.
 */
abstract class AmbientCredentialTestCase extends FeatureTestCase
{
    protected const SECRET   = 'ambient-credential-secret-key';
    protected const ATTACKER = 'http://attacker.example';

    /**
     * Fixture settings (nested format) + per-test overrides.
     *
     * @param array<string,mixed> $overrides
     */
    protected function applySettings(array $overrides): void
    {
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'general'       => ['decryption_key' => self::SECRET],
            'authorization' => ['enabled' => '1'],
            'register'      => ['enabled' => true, 'new_user_profile' => 'subscriber'],
            // The fixture allows CORS "*", which trusts the Authorization header from every origin.
            'cors'          => ['enabled' => 0],
        ], $overrides));
    }

    /**
     * @return array{email: string, password: string, id: int, jwt: string}
     */
    protected function newUser(bool $administrator): array
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $userId = $this->lookupUserId($email);
        if ($administrator) {
            $this->promoteToAdmin($userId);
        }

        return [
            'email'    => $email,
            'password' => $password,
            'id'       => $userId,
            'jwt'      => (string) $this->getJWTForUser($email, $password),
        ];
    }

    protected function userLoginExists(string $login): bool
    {
        $table   = self::getTablePrefix() . 'users';
        $escaped = self::$dbCon->real_escape_string($login);

        return self::$dbCon->query("SELECT ID FROM `{$table}` WHERE user_login = '{$escaped}' LIMIT 1")->num_rows > 0;
    }

    protected function userIdExists(int $userId): bool
    {
        $table = self::getTablePrefix() . 'users';

        return self::$dbCon->query("SELECT ID FROM `{$table}` WHERE ID = {$userId} LIMIT 1")->num_rows > 0;
    }

    protected function postExists(string $title, string $postType): bool
    {
        $table   = self::getTablePrefix() . 'posts';
        $escaped = self::$dbCon->real_escape_string($title);

        return self::$dbCon->query(
            "SELECT ID FROM `{$table}` WHERE post_title = '{$escaped}' AND post_type = '{$postType}' LIMIT 1"
        )->num_rows > 0;
    }

    /**
     * @param string               $method   HTTP verb the endpoint expects.
     * @param string               $path     Path + query, e.g. "/?rest_route=/wp/v2/users".
     * @param array<string,mixed>  $params
     * @param string               $encoding form | json | query
     * @param bool                 $navigate Deliver as a top-level GET navigation with `_method`.
     * @param array<string,string> $headers
     */
    protected function send(
        string $method,
        string $path,
        array $params,
        string $encoding,
        bool $navigate,
        array $headers
    ): ResponseInterface {
        $options = ['http_errors' => false, 'allow_redirects' => false, 'headers' => $headers];
        $url     = self::API_URL . $path;

        if ($navigate) {
            $query = array_merge($method === 'GET' ? [] : ['_method' => $method], $params);
            $url  .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);

            return $this->client->request('GET', $url, $options);
        }

        if ($encoding === 'query' || $method === 'GET') {
            if (!empty($params)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
            }

            return $this->client->request($method, $url, $options);
        }

        $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        $options['body'] = http_build_query($params);
        if ($encoding === 'json') {
            $options['headers']['Content-Type'] = 'application/json';
            $options['body'] = json_encode($params);
        }

        return $this->client->request($method, $url, $options);
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function json(ResponseInterface $response)
    {
        $response->getBody()->rewind();
        $decoded = json_decode($response->getBody()->getContents(), true);

        return is_array($decoded) ? $decoded : null;
    }
}
