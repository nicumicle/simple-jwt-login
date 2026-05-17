<?php

namespace SimpleJwtLoginTests\WP;

use Faker\Factory;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * Base class for WP integration tests.
 *
 * Uses WP_REST_Request + rest_do_request() to dispatch requests in-process —
 * no HTTP server or Guzzle needed. WordPress is fully booted via bootstrap-wp.php.
 *
 * ## Design notes
 *
 * The plugin's api.php captures $_REQUEST inside a rest_api_init closure:
 *
 *   $request = array_merge($_REQUEST, ParseRequest::process($_SERVER)['variables']);
 *   register_rest_route(..., function() use ($request) { ... });
 *
 * Therefore $_REQUEST must be populated BEFORE do_action('rest_api_init') fires,
 * and the REST server must be reset per-request so the closure captures fresh params.
 *
 * Error paths: the plugin calls wp_send_json_error() which, in AJAX contexts
 * (DOING_AJAX=true, set in bootstrap-wp.php), routes through wp_die() →
 * wp_die_ajax_handler filter.  We intercept that filter per-dispatch to throw
 * WPDieException instead of calling raw die(), then reconstruct the response
 * from the buffered JSON output.
 *
 * ## Lifecycle
 *
 *   setUpBeforeClass()   — call configurePlugin() here (outside any DB transaction)
 *   tearDownAfterClass() — call delete_option() to restore plugin option state
 */
abstract class WPTestCase extends WP_UnitTestCase
{
    /**
     * No-op override: the parent calls PHPUnit\Util\Test::parseTestMethodAnnotations()
     * which was removed in PHPUnit 10. We don't use @expectedDeprecated annotations,
     * so skipping this is safe.
     */
    public function expectDeprecated(): void
    {
    }

    /**
     * @param string               $method
     * @param string               $route
     * @param array<string,mixed>  $params
     * @param array<string,string> $headers
     */
    protected function request(string $method, string $route, array $params = [], array $headers = []): WP_REST_Response
    {
        return $this->dispatch($method, $route, $params, $headers);
    }

    /**
     * @param string               $method
     * @param string               $route
     * @param array<string,mixed>  $body
     * @param array<string,string> $headers
     */
    protected function jsonRequest(string $method, string $route, array $body = [], array $headers = []): WP_REST_Response
    {
        return $this->dispatch($method, $route, $body, $headers, true);
    }

    /**
     * @param string               $method
     * @param string               $route
     * @param array<string,mixed>  $params
     * @param array<string,string> $headers
     * @param bool                 $asJson
     */
    private function dispatch(string $method, string $route, array $params, array $headers, bool $asJson = false): WP_REST_Response
    {
        $originalRequest = $_REQUEST;
        $prevMethod      = $_SERVER['REQUEST_METHOD'] ?? null;

        // Populate $_REQUEST so the plugin's rest_api_init closure captures the params.
        $_REQUEST = array_merge($_REQUEST, $params);

        // Set REQUEST_METHOD so that ServerHelper (created inside the rest_api_init
        // closure) returns the correct method. Services like ResetPasswordService
        // switch on this value to choose between their sub-operations (e.g. PUT vs POST).
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);

        // Inject all custom headers into $_SERVER before rest_api_init fires.
        // ServerHelper reads from $_SERVER['HTTP_*'], so headers must be present
        // before the closure captures them (e.g. x-api-key for API key middleware).
        $injectedServerKeys = [];
        foreach ($headers as $key => $value) {
            $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$serverKey] = $value;
            $injectedServerKeys[] = $serverKey;
        }

        // Capture the HTTP status code that wp_send_json_error() passes via
        // status_header() before it calls wp_die(). This is the only way to
        // recover the intended HTTP status for error responses.
        $capturedStatus = 400;
        $statusCapture  = static function (string $header, int $code) use (&$capturedStatus): string {
            $capturedStatus = $code;
            return $header;
        };
        add_filter('status_header', $statusCapture, PHP_INT_MAX, 2);

        // Intercept wp_die_ajax_handler so error paths throw WPDieException
        // (catchable) instead of calling raw die() via _ajax_wp_die_handler.
        // (DOING_AJAX=true in bootstrap-wp.php routes wp_die() to this filter.)
        $ajaxDieInterceptor = static function () {
            return static function () {
                throw new \WPDieException('wp_die called from REST error path');
            };
        };
        add_filter('wp_die_ajax_handler', $ajaxDieInterceptor, PHP_INT_MAX);

        // Reset the REST server so rest_api_init re-runs with the current $_REQUEST.
        $GLOBALS['wp_rest_server'] = null;
        do_action('rest_api_init');

        $req = new WP_REST_Request($method, $route);

        if ($asJson) {
            $req->set_body((string) json_encode($params));
            $req->set_header('Content-Type', 'application/json');
        } else {
            foreach ($params as $key => $value) {
                $req->set_param($key, $value);
            }
        }

        foreach ($headers as $key => $value) {
            $req->set_header($key, $value);
        }

        ob_start();
        $response = null;
        try {
            $response = rest_do_request($req);
            ob_end_clean();
        } catch (\WPDieException $e) {
            $output   = ob_get_clean();
            $decoded  = json_decode((string) $output, true) ?? [];
            $response = new WP_REST_Response($decoded, $capturedStatus);
        } finally {
            remove_filter('status_header', $statusCapture, PHP_INT_MAX);
            remove_filter('wp_die_ajax_handler', $ajaxDieInterceptor, PHP_INT_MAX);
            $_REQUEST = $originalRequest;
            if ($prevMethod === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $prevMethod;
            }
            foreach ($injectedServerKeys as $k) {
                unset($_SERVER[$k]);
            }
        }

        return $response;
    }

    /**
     * Create a WP user via the test factory (automatically rolled back per test).
     *
     * @param array<string,mixed> $args
     * @return array{string, string, int}  [email, password, user_id]
     */
    protected function createUser(array $args = []): array
    {
        $faker    = Factory::create();
        $email    = $args['user_email'] ?? ($faker->randomNumber(6) . $faker->email());
        $password = $args['user_pass']  ?? 'password123';
        $login    = $args['user_login'] ?? ('user_' . $faker->randomNumber(6));

        $userId = $this->factory->user->create([
            'user_login' => $login,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => $args['role'] ?? 'subscriber',
        ]);

        return [$email, $password, $userId];
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
     * Write plugin settings. Call from setUpBeforeClass() so options are committed
     * before any DB transaction begins and are visible to all rest_api_init calls.
     *
     * Also clears SimpleJWTLoginSettings::$settingsInstances (a private static cache)
     * via reflection so that the next request picks up the freshly written option
     * rather than serving a stale cached instance from a prior test class.
     *
     * @param array<string,mixed> $settings
     */
    protected static function configurePlugin(array $settings): void
    {
        $prop = (new \ReflectionClass(SimpleJWTLoginSettings::class))
            ->getProperty('settingsInstances');
        $prop->setAccessible(true);
        $prop->setValue(null, []);

        update_option(SimpleJWTLoginSettings::OPTIONS_KEY, json_encode($settings));
    }
}
