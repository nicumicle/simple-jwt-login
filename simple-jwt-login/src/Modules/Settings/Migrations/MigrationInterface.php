<?php

namespace SimpleJWTLogin\Modules\Settings\Migrations;

interface MigrationInterface
{
    /**
     * @return int
     */
    public function getSourceVersion();

    /**
     * @return int
     */
    public function getTargetVersion();

    /**
     * @param array $settings
     * @return array
     */
    public function migrate($settings);
}
