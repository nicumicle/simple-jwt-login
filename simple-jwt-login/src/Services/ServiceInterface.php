<?php
namespace SimpleJWTLogin\Services;

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

interface ServiceInterface
{
    /**
     * @param SimpleJWTLoginSettings $settings
     * @return $this
     */
    public function withSettings(SimpleJWTLoginSettings $settings);

    /**
     * @param array $request
     * @return $this
     */
    public function withRequest($request);

    /**
     * @param array $session
     * @return $this
     */
    public function withSession($session);

    /**
     * @param array $cookies
     * @return $this
     */
    public function withCookies($cookies);

    /**
     * @param ServerHelper $serverHelper
     * @return $this
     */
    public function withServerHelper(ServerHelper $serverHelper);

    /**
     * @param int|null $actionName
     * @return mixed
     */
    public function makeAction($actionName = null);
}
