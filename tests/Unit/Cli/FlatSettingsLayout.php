<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use SimpleJWTLogin\Cli\SettingsLayout;

/**
 * Accepts any key and leaves settings as they are, so command tests can use small flat settings.
 * SettingsLayoutTest covers the real layout.
 */
class FlatSettingsLayout extends SettingsLayout
{
    public function normalize(array $settings)
    {
        return $settings;
    }

    public function assertKey($key)
    {
    }

    public function assertWritable($key, array $settings)
    {
    }
}
