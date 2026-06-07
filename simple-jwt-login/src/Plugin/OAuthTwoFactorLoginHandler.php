<?php

namespace SimpleJWTLogin\Plugin;

use Exception;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Modules\Jwt\JwtWrapper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Services\Oauth\AbstractOauth;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

/**
 * Handles the browser-based OAuth + 2FA login page.
 *
 * Registered for the WordPress login_form_{action} hook where action equals
 * AbstractOauth::BROWSER_2FA_ACTION ('sjl-oauth-2fa'). The flow is:
 *
 *  1. OAuthService exchanges the GitHub/Google/… code and detects 2FA is required.
 *  2. It generates an interim JWT and redirects the browser here:
 *       wp-login.php?action=sjl-oauth-2fa&sjl_jwt=<jwt>&redirect_to=<url>
 *  3. handleAction() (GET) validates the JWT and renders the HTML 2FA form.
 *  4. The user submits their 2FA code; handleAction() (POST) verifies it.
 *  5. On success the WordPress auth cookie is set and the browser is redirected.
 */
class OAuthTwoFactorLoginHandler
{
    /** @var array */
    private $serverVars;

    /** @var array */
    private $getVars;

    /** @var array */
    private $postVars;

    /**
     * @var SimpleJWTLoginSettings
     */
    private $settings;

    /**
     * @param array $serverVars  $_SERVER
     * @param array $getVars     $_GET
     * @param array $postVars    $_POST
     * @param SimpleJWTLoginSettings $settings
     */
    public function __construct($serverVars, $getVars, $postVars, $settings)
    {
        $this->serverVars = $serverVars;
        $this->getVars    = $getVars;
        $this->postVars   = $postVars;
        $this->settings = $settings;
    }

    protected function getBridge()
    {
        return new TwoFactorBridge();
    }

    public function handleAction()
    {
        $method = isset($this->serverVars['REQUEST_METHOD'])
            ? strtoupper((string) $this->serverVars['REQUEST_METHOD'])
            : 'GET';

        try {
            if ($method === 'POST') {
                $this->handlePost();
                return;
            }
            $this->handleGet();
        } catch (Exception $exception) {
            $this->abortToLoginPage($exception->getMessage());
        }
    }

    protected function handleGet()
    {
        $jwt        = isset($this->getVars['sjl_jwt'])     ? (string) $this->getVars['sjl_jwt']     : '';
        $redirectTo = isset($this->getVars['redirect_to']) ? (string) $this->getVars['redirect_to'] : admin_url();
        $error      = isset($this->getVars['sjl_error'])   ? (string) $this->getVars['sjl_error']   : '';

        $this->decodeAndValidate($jwt);

        $this->renderForm($jwt, $redirectTo, $error);
    }

    /**
     * @return void
     * @throws Exception
     * @SuppressWarnings(ExitExpression)
     */
    protected function handlePost()
    {
        $jwt        = isset($this->postVars['sjl_jwt'])     ? (string) $this->postVars['sjl_jwt']     : '';
        $code       = isset($this->postVars['sjl_code'])    ? (string) $this->postVars['sjl_code']    : '';
        $redirectTo = isset($this->postVars['redirect_to']) ? (string) $this->postVars['redirect_to'] : admin_url();

        $payload       = $this->decodeAndValidate($jwt);
        $userId        = (int)    $payload['tfa_user_id'];
        $nonce         = (string) $payload['tfa_nonce'];
        $providerClass = (string) $payload['tfa_provider'];

        $bridge = $this->getBridge();

        $user = get_user_by('id', $userId);
        if (!$user) {
            throw new Exception(esc_html(__('User not found.', 'simple-jwt-login')));
        }

        if ($bridge->isRateLimited($user)) {
            $this->renderForm($jwt, $redirectTo, __('Too many failed attempts. Please wait before trying again.', 'simple-jwt-login'));
            return;
        }

        if (!$bridge->verifyNonce($userId, $nonce)) {
            throw new Exception(esc_html(__('Session expired. Please log in again.', 'simple-jwt-login')));
        }

        if (empty($code)) {
            $this->renderForm($jwt, $redirectTo, __('Please enter your two-factor code.', 'simple-jwt-login'));
            return;
        }

        if (!$bridge->verifyCode($providerClass, $user, $code, $userId)) {
            $this->renderForm($jwt, $redirectTo, __('Invalid two-factor code. Please try again.', 'simple-jwt-login'));
            return;
        }

        $bridge->deleteNonce($userId);
        $wordPressData = $this->settings->getWordPressData();

        $wordPressData->loginUser($user, null);
        if ($this->settings->getGeneralSettings()->isSafeRedirectEnabled()) {
            $wordPressData->redirectSafe($redirectTo);
        }

        $wordPressData->redirect($redirectTo);
    }

    /**
     * Decode and validate the interim JWT. Returns the payload array.
     *
     * @param string $jwt
     * @return array
     * @throws Exception
     */
    protected function decodeAndValidate($jwt)
    {
        if (empty($jwt)) {
            throw new Exception(esc_html(__('Missing authentication token.', 'simple-jwt-login')));
        }

        $jwtWrapper = new JwtWrapper();

        $decoded = $jwtWrapper->decode(
            $jwt,
            JwtKeyFactory::getFactory($this->settings)->getPublicKey(),
            [$this->settings->getGeneralSettings()->getJWTDecryptAlgorithm()]
        );
        $payload = (array) $decoded;

        if (empty($payload[AuthenticateService::TFA_PENDING_CLAIM])) {
            throw new Exception(esc_html(__('Invalid authentication token.', 'simple-jwt-login')));
        }

        if (empty($payload['tfa_user_id']) || empty($payload['tfa_nonce'])) {
            throw new Exception(esc_html(__('Invalid authentication token.', 'simple-jwt-login')));
        }

        return $payload;
    }

    /**
     * Render the 2FA form within the WordPress login page frame.
     *
     * @param string $jwt
     * @param string $redirectTo
     * @param string $error
     * @return void
     * @SuppressWarnings(ExitExpression)
     */
    protected function renderForm($jwt, $redirectTo, $error)
    {
        login_header(__('Two-Factor Authentication', 'simple-jwt-login'));
        $allowedHtml = array(
            'div'   => array('id' => true),
            'form'  => array('name' => true, 'id' => true, 'action' => true, 'method' => true),
            'input' => array(
                'type'         => true,
                'name'         => true,
                'id'           => true,
                'value'        => true,
                'class'        => true,
                'size'         => true,
                'autocomplete' => true,
                'inputmode'    => true,
                'autofocus'    => true,
                'required'     => true,
            ),
            'p'     => array('class' => true),
            'label' => array('for' => true),
        );
        echo wp_kses($this->buildFormHtml($jwt, $redirectTo, $error), $allowedHtml);
        login_footer();
        exit;
    }

    /**
     * @param string $jwt
     * @param string $redirectTo
     * @param string $error
     * @return string
     */
    protected function buildFormHtml($jwt, $redirectTo, $error)
    {
        $formAction = esc_url(
            add_query_arg('action', AbstractOauth::BROWSER_2FA_ACTION, wp_login_url())
        );

        $html = '';
        if (!empty($error)) {
            $html .= '<div id="login_error">' . esc_html($error) . '</div>';
        }

        $html .= '<form name="loginform" id="loginform"'
            . ' action="' . $formAction . '" method="POST">'
            . '<input type="hidden" name="sjl_jwt" value="' . esc_attr($jwt) . '" />'
            . '<input type="hidden" name="redirect_to" value="' . esc_attr($redirectTo) . '" />'
            . '<p>'
            . '<label for="sjl-2fa-code">' . esc_html__('Two-Factor Code', 'simple-jwt-login') . '</label>'
            . '<input type="text" name="sjl_code" id="sjl-2fa-code"'
            . ' class="input" size="20"'
            . ' autocomplete="one-time-code" inputmode="numeric"'
            . ' autofocus required />'
            . '</p>'
            . '<p class="submit">'
            . '<input type="submit" name="wp-submit" id="wp-submit"'
            . ' class="button button-primary button-large"'
            . ' value="' . esc_attr__('Verify', 'simple-jwt-login') . '" />'
            . '</p>'
            . '</form>';

        return $html;
    }

    /**
     * @param string $message
     * @return void
     * @SuppressWarnings(ExitExpression)
     */
    protected function abortToLoginPage($message)
    {
        $url = add_query_arg('sjl_error', rawurlencode($message), wp_login_url());
        wp_safe_redirect($url);
        exit;
    }
}
