<?php

namespace SimpleJwtLoginTests\Feature;

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
     * @var array|null
     */
    protected static $initialOption;
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
//        if (self::$dbCon != null) {
//            static::$dbCon->close();
//        }
    }

    protected function initClient($extraOptions = []): void
    {
        $options = array_merge($extraOptions, ['http_errors' => false]);
        $this->client = new Client($options);
    }

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
     * @param array $newOption
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
                    "INSERT INTO %s (option_name, option_value) VALUES('%s', '%s');",
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
        if ($option != null) {
            self::$initialOption = json_decode($option, true);
        }
    }
}
