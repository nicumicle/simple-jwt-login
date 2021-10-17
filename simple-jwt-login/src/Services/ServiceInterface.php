<?php

namespace SimpleJWTLogin\Services;

use Exception;
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
     * @return mixed
     * @throws Exception
     */
    public function makeAction();
}
