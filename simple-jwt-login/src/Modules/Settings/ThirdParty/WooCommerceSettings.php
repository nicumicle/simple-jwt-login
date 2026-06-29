<?php

namespace SimpleJWTLogin\Modules\Settings\ThirdParty;

use SimpleJWTLogin\Modules\Settings\BaseSettings;

class WooCommerceSettings extends AbstractThirdPartySettings
{
    public function getGroup()
    {
        return 'woocommerce';
    }

    protected function getExtraFields()
    {
        return [
            ['store_api_disable_nonce', BaseSettings::SETTINGS_TYPE_BOL],
        ];
    }

    /**
     * Whether header-JWT requests may bypass the WooCommerce Store API CSRF
     * nonce check, enabling fully headless cart & checkout with the token alone.
     * Off by default - it relaxes CSRF protection, so it is an explicit opt-in.
     *
     * @return bool
     */
    public function isStoreApiNonceDisabled()
    {
        return $this->isFieldEnabled('store_api_disable_nonce');
    }
}
