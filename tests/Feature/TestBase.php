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
    protected $initialOption;

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
    private function getTablePrefix()
    {
        $env = $_ENV['WORDPRESS_TABLE_PREFIX'];
        if (empty($env)) {
            return "wp_";
        }

        return $env;
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @param array $newOption
     * @return void
     * @throws \Exception
     */
    protected function updateOption($newOption)
    {
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


        $table = $this->getTablePrefix() . "options";
        $resource = $dbCon->query(
            sprintf(
                "SELECT * FROM $table WHERE option_name='%s' LIMIT 1",
                SimpleJWTLoginSettings::OPTIONS_KEY
            )
        );
        $option = null;
        while ($rows = $resource->fetch_assoc()) {
            $option = $rows['option_value'];
        }
        $this->initialOption = json_decode($option, true);

        if ($option == null) {
            //INSERT
            $dbCon->query(
                sprintf(
                    "INSERT INTO %s (option_name, option_value) VALUES('%s', '%s');",
                    $table,
                    SimpleJWTLoginSettings::OPTIONS_KEY,
                    json_encode($newOption),
                )
            );
        } else {
            //UPDATE
            $dbCon->query(
                sprintf(
                    "UPDATE %s SET option_value='%s' WHERE option_name='%s' LIMIT 1;",
                    $table,
                    json_encode($newOption),
                    SimpleJWTLoginSettings::OPTIONS_KEY
                )
            );
        }
        $resource->free();
        $dbCon->close();
    }
}
