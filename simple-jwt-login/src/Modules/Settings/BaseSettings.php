<?php
namespace SimpleJWTLogin\Modules\Settings;

use SimpleJWTLogin\Modules\WordPressDataInterface;

abstract class BaseSettings
{
    const SETTINGS_TYPE_INT = 0;
    const SETTINGS_TYPE_BOL = 1;
    const SETTINGS_TYPE_STRING = 2;
    const SETTINGS_TYPE_ARRAY = 3;

    /**
     * @var SettingsErrors $settingsErrors
     */
    protected $settingsErrors;

    public function __construct()
    {
        $this->settingsErrors = new SettingsErrors();
    }
    /**
     * @var array|null
     */
    protected $settings;
    /**
     * @var array|null
     */
    protected $post;
    /**
     * @var WordPressDataInterface
     */
    protected $wordPressData;

    /**
     * @return array|null
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param array|null $post
     * @return $this
     */
    public function withPost($post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * @param array|null $settings
     * @return $this
     */
    public function withSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @param WordPressDataInterface $wordPressData
     * @return $this
     */
    public function withWordPressData($wordPressData)
	{
        $this->wordPressData = $wordPressData;

        return $this;
    }

    /**
     * @param null|string $propertyGroup
     * @param string $propertyName
     * @param null|string $postKeyGroup
     * @param string $postKey
     * @param int|null $type
     * @param string|int|boolean|null $defaultValue
     * @param null|bool $base64Encode
     */
    protected function assignSettingsPropertyFromPost(
        $propertyGroup,
        $propertyName,
        $postKeyGroup,
        $postKey,
        $type = null,
        $defaultValue = null,
        $base64Encode = null
    ) {
        $postKeyExists = $postKeyGroup !== null
            ? isset($this->post[$postKeyGroup]) && isset($this->post[$postKeyGroup][$postKey])
            : isset($this->post[$postKey]);

        if (!$postKeyExists) {
            if ($defaultValue !== null) {
                $defaultValue = $base64Encode
                    ? base64_encode($defaultValue)
                    : $defaultValue;

                $this->assignProperty($defaultValue, $propertyName, $propertyGroup);
            } elseif ($type === self::SETTINGS_TYPE_ARRAY) {
                $defaultValue = [];
                $this->assignProperty($defaultValue, $propertyName, $propertyGroup);
            }
            return;
        }

        $postValue = $postKeyGroup !== null
            ? $this->post[$postKeyGroup][$postKey]
            : $this->post[$postKey];
        switch ($type) {
            case self::SETTINGS_TYPE_INT:
                $value = intval($postValue);
                break;
            case self::SETTINGS_TYPE_BOL:
                $value = (bool)$postValue;
                break;
            case self::SETTINGS_TYPE_STRING:
                $value = $base64Encode
                    ? base64_encode($postValue)
                    : $this->wordPressData->sanitizeTextField($postValue);
                break;
            case self::SETTINGS_TYPE_ARRAY:
                $value = (array)$postValue;
                break;
            default:
                $value = $postValue;
                break;
        }
        $this->assignProperty($value, $propertyName, $propertyGroup);
    }

    private function assignProperty($value, $propertyName, $propertyGroup = null)
    {
        if ($propertyGroup !== null) {
            $this->settings[$propertyGroup][$propertyName] = $value;
            return;
        }
        $this->settings[$propertyName] = $value;
    }
}
