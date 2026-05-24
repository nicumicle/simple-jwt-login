<?php

namespace SimpleJWTLogin\Modules\Settings\ThirdParty;

use SimpleJWTLogin\Modules\Settings\BaseSettings;

class TwoFactorSettings extends AbstractThirdPartySettings
{
    const INTERIM_TTL_DEFAULT = 5;

    public function getGroup()
    {
        return 'two_factor';
    }

    protected function getExtraFields()
    {
        return [
            ['interim_ttl', BaseSettings::SETTINGS_TYPE_INT],
        ];
    }

    /**
     * TTL in minutes for the interim two-factor JWT.
     * @return int
     */
    public function getInterimTtl()
    {
        $ttl = (int) $this->get('interim_ttl', (string) self::INTERIM_TTL_DEFAULT);
        return $ttl > 0 ? $ttl : self::INTERIM_TTL_DEFAULT;
    }
}
