<?php

namespace SimpleJWTLogin\Modules\Settings;

use SimpleJWTLogin\Modules\WordPressDataInterface;

interface SettingsInterface
{
    public function initSettingsFromPost();
    public function validateSettings();

    /**
     * @param array $post
     * @return $this
     */
    public function withPost($post);

    /**
     * @param array|null $settings
     * @return $this
     */
    public function withSettings($settings);

    /**
     * @return array|null
     */
    public function getSettings();

    /**
     * @param WordPressDataInterface $wordPressData
     * @return $this
     */
    public function withWordPressData($wordPressData);
}
