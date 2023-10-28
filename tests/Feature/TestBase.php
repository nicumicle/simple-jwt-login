<?php

namespace SimpleJwtLoginTests\Feature;

use Faker\Factory;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class TestBase extends TestCase
{
    /**
     * @var Client|null
     */
    protected ?Client $client;

    const API_URL = 'http://localhost';

    /**
     * @var array<string,mixed>|null
     */
    protected static $initialOption = null;
    /**
     * @var \mysqli|null
     */
    protected static $dbCon;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::initCon();
        self::initDbDefaultOption();
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->initClient();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::updateOption(self::$initialOption);
    }

    /**
     * Init a GuzzleClient
     *
     * @param array<string,mixed> $extraOptions
     * @return void
     */
    protected function initClient($extraOptions = []): void
    {
        $options = array_merge($extraOptions, ['http_errors' => false]);
        $this->client = new Client($options);
    }

    /**
     * @param string $message
     * @param int|string $code
     * @return array<string,mixed>
     */
    protected static function generateErrorJson($message, $code): array
    {
        return [
            'success' => false,
            'data' => [
                'message' => $message,
                'errorCode' => $code,
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return string
     */
    protected static function getTablePrefix()
    {
        $env = $_ENV['WORDPRESS_TABLE_PREFIX'];
        if (empty($env)) {
            return "wp_";
        }

        return $env;
    }

    /**
     * Init the DB Connection
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return void
     * @throws \Exception
     */
    private static function initCon()
    {
        if (self::$dbCon != null) {
            return;
        }

        $dbCon = new \mysqli(
            $_ENV["WORDPRESS_DB_HOST"],
            $_ENV["WORDPRESS_DB_USER"],
            $_ENV["WORDPRESS_DB_PASSWORD"],
            $_ENV["WORDPRESS_DB_NAME"]
        );

        // Check connection
        if ($dbCon->connect_error) {
            throw new \Exception($dbCon->connect_error);
        }

        self::$dbCon = $dbCon;
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @param array<string,mixed> $newOption
     * @return void
     * @throws \Exception
     */
    protected static function updateOption($newOption)
    {
        $table = self::getTablePrefix() . "options";

        if (self::$initialOption === null) {
            //INSERT
            self::$dbCon->query(
                sprintf(
                    "INSERT IGNORE INTO %s (option_name, option_value) VALUES('%s', '%s');",
                    $table,
                    SimpleJWTLoginSettings::OPTIONS_KEY,
                    json_encode($newOption),
                )
            );
        } else {
            //UPDATE
            self::$dbCon->query(
                sprintf(
                    "UPDATE %s SET option_value='%s' WHERE option_name='%s' LIMIT 1;",
                    $table,
                    json_encode($newOption),
                    SimpleJWTLoginSettings::OPTIONS_KEY
                )
            );
        }
    }

    /**
     * Initializes the default options that are stored in the DB in a variable
     *
     * @return void
     */
    protected static function initDbDefaultOption()
    {
        $table = self::getTablePrefix() . "options";
        $resource = self::$dbCon->query(
            sprintf(
                "SELECT * FROM $table WHERE option_name='%s' LIMIT 1",
                SimpleJWTLoginSettings::OPTIONS_KEY
            )
        );
        $option = null;
        while ($rows = $resource->fetch_assoc()) {
            $option = $rows['option_value'];
        }

        if ($option !== null) {
            self::$initialOption = json_decode($option, true);
        } else {
            self::$initialOption = null;
        }
    }

    /**
     * @return array<int,string>
     */
    protected function registerRandomUser()
    {
        $faker = Factory::create();
        $email = $faker->randomNumber(6) . $faker->email();
        $password = "1234";

        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri, [
            'body' => json_encode(
                [
                    'email' => $email,
                    'password' => $password,
                ]
            ),
        ]);

        return [$email, $password, $result->getStatusCode(), $result->getBody()->getContents()];
    }

    /**
     * @param string $email
     * @param string $password
     * @return array<int,int|string>
     */
    protected function authUser($email, $password)
    {
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/auth";
        $result = $this->client->post($uri, [
            'body' => json_encode([
                'email' => $email,
                'password' => $password,
            ])
        ]);

        return [$result->getStatusCode(), $result->getBody()->getContents()];
    }

    /**
     * @param $jwt string
     * @return array<int,int|string>
     */
    protected function deleteUser($jwt)
    {
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->delete($uri, [
            'body' => json_encode([
                'JWT' => $jwt,
            ])
        ]);

        return [$result->getStatusCode(), $result->getBody()->getContents()];
    }

    /**
     * @param int $userID
     * @return array<string,mixed>
     */
    protected function getUserMeta($userID)
    {
        $table = self::getTablePrefix() . "usermeta";
        $resource = self::$dbCon->query(
            sprintf(
                "SELECT * FROM $table WHERE user_id='%d';",
                $userID
            )
        );
        $result = [];
        while ($rows = $resource->fetch_assoc()) {
            $result[$rows['meta_key']] = $rows['meta_value'];
        }

        return $result;
    }
}
