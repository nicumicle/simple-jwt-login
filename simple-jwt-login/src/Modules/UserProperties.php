<?php

namespace SimpleJWTLogin\Modules;

class UserProperties
{
    /**
     * @param array $userProperties
     * @param array $extraParameters
     *
     * @return array
     */
    public function build($userProperties, $extraParameters)
    {
        $diff = array_diff_key($extraParameters, $userProperties);

        if (!empty($extraParameters['user_login'])) {
            $userProperties['user_login'] = $extraParameters['user_login'];
        }

        return array_merge($userProperties, $diff);
    }

    public static function getAllowedUserProperties()
    {
        $typeString = 'string';
        $typeBool = 'bool';
        return [
            'password' => [
                'type' => $typeString,
                'description' => __('(string) The plain-text user password.', 'simple-jwt-login'),
                'updateable' => false,
            ],
            'email' => [
                'type' => $typeString,
                'description' => __('(string) The user email address.', 'simple-jwt-login'),
                'updateable' => false,
            ],
            'user_login' => [
                'type' => $typeString,
                'description' => __("(string) The user's login username.", 'simple-jwt-login'),
                'updateable' => true,
            ],
            'user_nicename' => [
                'type' => $typeString,
                'description' => __('(string) The URL-friendly user name.', 'simple-jwt-login'),
                'updateable' => true,
            ],
            'user_url' => [
                'type' => $typeString,
                'description' => __('(string) The user URL.', 'simple-jwt-login'),
                'updateable' => true,
            ],
            'display_name' => [
                'type' => $typeString,
                'description' => __("(string) The user's display name. Default is the user's username.", 'simple-jwt-login'),
                'updateable' => true,
            ],
            'nickname' => [
                'type' => $typeString,
                'description' => __("(string) The user's nickname. Default is the user's username.", 'simple-jwt-login'),
                'updateable' => true,
            ],
            'first_name' => [
                'type' => $typeString,
                'description' => __(
                    "(string) The user's first name. For new users, will be used to build the first"
                    . " part of the user's display name if \$display_name is not specified.",
                    'simple-jwt-login'
                ),
                'updateable' => true,
            ],
            'last_name' => [
                'type' => $typeString,
                'description' => __(
                    "(string) The user's last name. For new users, will be used to build the second"
                    . " part of the user's display name if \$display_name is not specified.",
                    'simple-jwt-login'
                ),
                'updateable' => true,
            ],
            'description' => [
                'type' => $typeString,
                'description' => __("(string) The user's biographical description.", 'simple-jwt-login'),
                'updateable' => true,
            ],
            'rich_editing' => [
                'type' => $typeBool,
                'description' => __(
                    "(string) Whether to enable the rich-editor for the user."
                    . " Accepts 'true' or 'false' as a string literal, not boolean. Default 'true'.",
                    'simple-jwt-login'
                ),
                'updateable' => true,
            ],
            'syntax_highlighting' => [
                'type' => $typeBool,
                'description' => __(
                    "(string) Whether to enable the rich code editor for the user."
                    . " Accepts 'true' or 'false' as a string literal, not boolean. Default 'true'.",
                    'simple-jwt-login'
                ),
                'updateable' => true,
            ],
            'comment_shortcuts' => [
                'type' => $typeString,
                'description' => __(
                    "(string) Whether to enable comment moderation keyboard shortcuts for the user."
                    . " Accepts 'true' or 'false' as a string literal, not boolean. Default 'false'.",
                    'simple-jwt-login'
                ),
                'updateable' => true,
            ],
            'admin_color' => [
                'type' => $typeString,
                'description' => __("(string) Admin color scheme for the user. Default 'fresh'.", 'simple-jwt-login'),
                'updateable' => true,
            ],
            'use_ssl' => [
                'type' => $typeBool,
                'description' => __('(bool) Whether the user should always access the admin over https. Default false.', 'simple-jwt-login'),
                'updateable' => true,
            ],
            'user_registered' => [
                'type' => $typeString,
                'description' => __("(string) Date the user registered. Format is 'Y-m-d H:m:s'.", 'simple-jwt-login'),
                'updateable' => true,
            ],
            'user_activation_key' => [
                'type' => $typeString,
                'description' => __('(string) Password reset key. Default empty.', 'simple-jwt-login'),
                'updateable' => true,
            ],
            'spam' => [
                'type' => $typeBool,
                'description' => __('(bool) Multisite only. Whether the user is marked as spam. Default false.', 'simple-jwt-login'),
                'updateable' => true,
            ],
            'show_admin_bar_front' => [
                'type' => $typeString,
                'description' => __(
                    "(string) Whether to display the Admin Bar for the user on the site's front end."
                    . " Accepts 'true' or 'false' as a string literal, not boolean. Default 'true'.",
                    'simple-jwt-login'
                ),
                'updateable' => true,
            ],
            'locale' => [
                'type' => $typeString,
                'description' => __("(string) User's locale. Default empty.", 'simple-jwt-login'),
                'updateable' => true,
            ],
        ];
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public static function getExtraParametersFromRequest(array $request)
    {
        $requestParameters = self::getAllowedUserProperties();
        $requestParameterKeys = array_keys($requestParameters);
        $return = [];
        foreach ($requestParameterKeys as $key) {
            if (isset($requestParameters[$key]['updateable'])
                && $requestParameters[$key]['updateable'] === false) {
                continue;
            }
            if (isset($request[$key])) {
                $return[$key] = $request[$key];
            }
        }

        return $return;
    }
}
