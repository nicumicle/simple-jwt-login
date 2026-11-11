<?php

namespace SimpleJWTLogin\Modules\Settings;

use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

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
     * Rebuild the admin-form POST shape from the current settings.
     *
     * @return array
     */
    public function buildPostFromSettings();

    /**
     * @param WordPressDataInterface $wordPressData
     * @return $this
     */
    public function withWordPressData($wordPressData);
}
