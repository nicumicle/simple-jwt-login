=== Simple JWT Login – Allows you to use JWT on REST endpoints. ===

Contributors: nicu_m
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PK9BCD6AYF58Y&source=url
Tags: jwt, API, auto login, register, tokens
Requires at least: 4.4.0
Tested up to: 6.6
Requires PHP: 5.5
Stable tag: 3.5.6
License: GPLv3
License URI: https://www.gnu.org/licenses/quick-guide-gplv3.html

The main purpose of this plugin is to allow Mobile apps, or other websites to access the content via REST endpoints in a secure way.

== Description ==

Simple JWT Login is a FREE WordPress plugin that allows you to use a JWT on WordPress REST endpoints.

This plugin allows you to log in, register, authenticate, delete and change user password to a WordPress website using a JWT.

It's main purpose is to allow you to connect a mobile App or other websites with WordPress. 

Plugin Documentation Site: [https://simplejwtlogin.com](https://simplejwtlogin.com?utm_source=readme)

== Some awesome features ==

* Auto-login using JWT and AUTH_KEY
* Register new users via API
* Delete WordPress users based on a JWT
* Reset user password
* Allow auto-login / register / delete users only from specific IP addresses
* Allow register users only from a specific domain name
* API Route for generating new JWT
* Get JWT from URL, SESSION, COOKIE or HEADER
* Pass request parameters to login URL
* CORS settings for plugin Routes
* Hooks
* JWT Authentication
* Allow access private endpoints with JWT
* Protect endpoints with JWT

Check the plugin [website](https://simplejwtlogin.com) for more features.

== Login User ==

This plugin is customizable and offers you multiple methods to log in to you website, based on multiple scenarios.

In order to login, users have to send JWT. The plugin, validates the JWT, and if everything is OK, it can extract the WordPress email address or user ID.
Users can specify the exact key of the JWT payload where this information can be found.

Here are the methods how you can send the JWT in order to auto-login:

1. URL
2. Header
3. Cookie
4. Session

If the JWT is present in multiple places ( like URL and Header), the JWT will be overwritten.

This plugin supports multiple JWT Decryption algorithms, like: HS256, HS512, HS384, RS256,RS384 and RS512.

After the user is logged in you can automatically redirect the user to a page like:

- Dashboard
- Homepage
- or any other custom Page ( this is mainly used for redirecting users to a landing page)

You can attach to your redirect a URL parameter `redirectUrl` that will be used for redirect instead of the defined ones.
In order to use this, you have to enable it by checking the option `Allow redirect to a specific URL`.

Also, redirect after login offers some variables that you can use in the customURL and redirectUrl.
Here are the variables which you can use in your URL:
- {{site_url}} : Site URL
- {{user_id}} : Logged in user ID
- {{user_email}} : Logged in user email
- {{user_login}} : Logged in username
- {{user_first_name}} : User first name
- {{user_last_name}} : User last name
- {{user_nicename}} : User nice name

You can generate dynamic URLs with these variables, and, before the redirect, the specific value will be replaced.

Here is an example:

``
    http://yourdomain.com?param1={{user_id}}&param2={{user_login}}
`` 

Also, this plugin allows you to limit the auto-login based on the client IP address.
If you are concerned about security, you can limit the auto-login only from some IP addresses.

[Read more](https://simplejwtlogin.com/docs/autologin/) on our website. 

== Register Users ==

This plugin also allows you to create WordPress users.

This option is disabled by default, but you can enable it at any time.

In order to create users, you just have to make a POST request to the route URL, and send an *email* and a *password* as parameter and the new user will be created.

You can select the type for the new users: editor, author, contributor, subscriber, etc.

Also, you can limit the user creating only for specific IP addresses, or  specific email domains.

Another cool option is "Generate a random password when a new user is created".
If this option is selected, the password is no more required when a new user is created a random password will be generated.

Another option that you have for register user is "Initialize force login after register".
When the user registration is completed, the user will continue on the flow configured on login config.

If auto-login is disabled, this feature will not work and the register user will go on a normal flow and return a json response.

If you want to add custom user_meta on user creation, just add the parameter `user_meta` with a json. This will create user_meta for the new user.

``
{
    "meta_key":"meta_value",
    "meta_key2":"meta_value"
}
``

These properties can be passed in the request when the new user is created.

- **email** : (required) (string)  The user email address.
- **password** :  (required) (string) The plain-text user password.
- **user_login** : (string) The user's login username.
- **user_nicename** : (string) The URL-friendly username.
- **user_url** : (string) The user URL.
- **display_name** : (string) The user's display name. Default is the user's username.
- **nickname** : (string) The user's nickname. Default is the user's username.
- **first_name** : (string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
- **last_name** : (string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
- **description** : (string) The user's biographical description.
- **rich_editing** : (string) Whether to enable the rich-editor for the user. Accepts 'true' or 'false' as a string literal, not boolean. Default 'true'.
- **syntax_highlighting** : (string) Whether to enable the rich code editor for the user. Accepts 'true' or 'false' as a string literal, not boolean. Default 'true'.
- **comment_shortcuts** : (string) Whether to enable comment moderation keyboard shortcuts for the user. Accepts 'true' or 'false' as a string literal, not boolean. Default 'false'.
- **admin_color** : (string) Admin color scheme for the user. Default 'fresh'.
- **use_ssl** : (bool) Whether the user should always access the admin over https. Default false.
- **user_registered** : (string) Date the user registered. Format is `Y-m-d H:m:s`.
- **user_activation_key** : (string) Password reset key. Default empty.
- **spam** : (bool) Multisite only. Whether the user is marked as spam. Default false.
- **show_admin_bar_front** : (string) Whether to display the Admin Bar for the user on the site's front end. Accepts 'true' or 'false' as a string literal, not boolean. Default 'true'.
- **locale** : (string) User's locale. Default empty.

[Read More](https://simplejwtlogin.com/docs/register-user/) on our website.

== Delete User ==

Delete user it is disabled by default.

In order to delete a user, you have to configure where to search the details in the JWT.
You can delete users by WordPress User ID or by Email address.

Also, you have to choose the JWT parameter key where email or user ID it is stored in the JWT.

Also, you can limit the deletion of users to specific IP addresses for security reasons.

== Reset Password ==

Reset password and change password endpoints are disabled by default.

This plugin allows you to send the reset password endpoint, just by calling an endpoint. An email with the code will be sent to a specific email address.

Also, you are able to customize this email, or even not send at email at all.

The change password endpoint, changes the user password, based on the reset password code.

[Read More](https://simplejwtlogin.com/docs/delete-user/) on our website.

== Authentication ==

This plugin allows users to generate JWT tokens based from WordPress user email and password.

In order to Get a new JWT, just make a POST request to */auth* route with your WordPress email and password ( or password_hash) and the response will look something like this:

``
     {
         "success": true,
         "data": {
             "jwt": "NEW_GENERATED_JWT_HERE"
         }
     }
``

If you want to add extra parameters in the JWT payload, just send the parameter `payload` on `/auth` endpoint, and add a json with the values you want to be added in the payload.

At some point, the JWT will expire.
So, if you want to renew it without having to ask again for user and password, you will have to make a POST request to the *auth/refresh* route.

This will generate a response with a new JWT, similar to the one that `/auth` generates.

If you want to get some details about a JWT, and validate that JWT, you can call `/auth/validate`. If you have a valid JWT, details about the available WordPress user will be returned, and some JWT details.

If you want to revoke a JWT, access `/auth/revoke` and send the `jwt` as a parameter.

The plugin auto-generates the example URL you might need to test these scenarios.

[Read More](https://simplejwtlogin.com/docs/authentication/) on our website.

== Auth codes ==

Auth codes are optional, but you can enable them for Auto-login, Register User and Delete user.

This feature allows you to add a layer of protection to your API routes.

The Auth codes contains 3 parts:
1. Authentication Key: This is the actual code that you have to add in the request.
2. WordPress new User Role: can be used when you want to create multiple user types with the create user endpoint. If you leave it blank, the value configured in the 'Register Settings' will be used.
3. Expiration Date: This allows you to set an expiration date for you auth codes. The format is `Y-M-D H:m:s'. Example : 2020-12-24 23:00:00. If you leave it blank, it will never expire.

Expiration date format: year-month-day hours:minutes:seconds

[Read More](https://simplejwtlogin.com/docs/auth-codes/) on our website.

== Hooks ==

This plugin allows advanced users to link some hooks with the plugin and perform some custom scripts.
Some available hooks:

- **simple_jwt_login_login_hook**
  - type: action
  - parameters: Wp_User $user
  - description: This hook it is called after the user has been logged in. 
  
- **simple_jwt_login_redirect_hook**
  - type: action
  - parameters: string $url, array $request
  - description: This hook it is called before the user it will be redirected to the page he specified in the login section. 
  
- **simple_jwt_login_register_hook**
  - type: action
  - parameters: Wp_User $user, string $plain_text_password
  - description: This hook it is called after a new user has been created.  
  
- **simple_jwt_login_delete_user_hook**
  - type: action
  - parameters: Wp_User $user
  - description: This hook it is called right after the user has been deleted.

- **simple_jwt_login_jwt_payload_auth**
  - type: filter
  - parameters: array $payload, array $request
  - return: array $payload
  - description: This hook is called on /auth endpoint. Here you can modify payload parameters. 

- **simple_jwt_login_no_redirect_message**
  - type: filter
  - parameters: array $payload, array $request
  - return: array $payload
  - description: This hook is called on /autologin endpoint when the option `No Redirect` is selected. You can customize the message and add parameters.

- **simple_jwt_login_reset_password_custom_email_template**
  - type: filter
  - parameters: string $template, array $request
  - return: string $template
  - description: This is executed when POST /user/reset_password is called. It will replace the email template that has been added in Reset Password settings  

View full list of hooks on [https://simplejwtlogin.com/docs/hooks](https://simplejwtlogin.com/docs/hooks).

== CORS ==

The CORS standard it is needed because it allows servers to specify who can access its assets and how the assets can be accessed.
Cross-origin requests are made using the standard HTTP request methods like GET, POST, PUT, DELETE, etc.

[Read More](https://simplejwtlogin.com/docs/cors/) on our website.

== Protect endpoints ==

This option is disabled by default. In order to enable it, you need to set "Protect endpoints enabled" to true.

This feature comes with 2 actions:
- Apply on All REST Endpoints
- Apply only on specific REST endpoints

When you choose `Apply on All REST Endpoints`, you will be able to whitelist some endpoints from your WordPress REST by adding them to the whitelist section.
For example, If you only want to allow users to access the `wp/v2/posts` endpoint without having to provide the JWT, you save in the whitelist section `wp/v2/posts`

When you choose `Apply only on specific endpoints`, you will have to add all the endpoints you want to be protected by JWT.

When an endpoint is protected, and you don't provide a JWT, you will get the following response:

``
{
   "success":false,
   "data":{
      "message":"Your are not authorized to access this endpoint.",
      "errorCode":403,
      "type":"simple-jwt-login-route-protect"
   }
}
``

[Read More](https://simplejwtlogin.com/docs/protect-endpoints/) on our website.

== Integration ==

**PHP** 

In order to easily integrate your app/site with simple-jwt-login, we have developed a composer package.

``
composer require nicumicle/simple-jwt-login-client-php
``

You can check the [package page](https://packagist.org/packages/nicumicle/simple-jwt-login-client-php) for more details and code examples.

**Javascript**

Also, there is a [Javascript SDK](https://github.com/simple-jwt-login/js-sdk) that you can install with `npm` or `yarn`.

``
npm install "simple-jwt-login"
``
or 

``
yarn add "simple-jwt-login"
``

== Screenshots ==

1. Dashboard
2. General Settings for JWT
3. Auto-login configuration
4. Register new users configuration
5. Delete user configuration
6. Reset Password configuration   
7. Authentication configuration for generating and refresh Json Web Tokens
8. Auth Codes
9. Available Hooks
10. CORS
11. Protect endpoints

== Installation ==

Here's how you install and activate the JWT-login plugin:

1. Download the Simple-JWT-login plugin.
2. Upload the .zip file in your WordPress plugin directory.
3. Activate the plugin from the "Plugins" menu in WordPress.

or

1. Go to the 'Plugins' menu in WordPress and click 'Add New'
2. Search for 'Simple JWT Login' and select 'Install Now'
3. Activate the plugin when prompted

Next steps:

- Go to "General section"
    - set "JWT Decryption key". With this key, we will validate the JWT.
    - choose "JWT Decryption algorithm".

- Go to "Login Settings"
    - please set "Allow Auto-login" to "yes".
    - set parameter "Action" ( Login by WordPress User ID / User Email).
    - set the "JWT parameter key" with the key from your JWT where user email or user ID can be found in the decoded JWT.

After that, you can copy the sample URL from the top of the page ( Login Config section), replace the JWT string with your valid JWT, and you will be redirected to your WordPress and automatically logged in.

Also, if you don't want to add the JWT in the URL, you can add it in the header of the request with the key 'Authorization'.
Please note that the JWT that is set in the header overwrites the one from the URL.

Example:

``
Authorization: Bearer YOURJWTTOKEN
``

or

``
Authorization: YOURJWTTOKEN
``

== Frequently Asked Questions ==

= Is this plugin secure? =
Yes, this plugin is secure. It allows to auto-login to your WordPress website using a JWT, that is decrypted and validated against your JWT Decryption key.
Make sure you set the specific user type when new users are created.

= Can I disable the API for registering new users? =
Yes, both Auto-login and register can be enabled or disabled.

= Can I limit the email addresses that can register in WordPress with this plugin? =
Yes, You can use the domain limitation and add multiple domains separated by comma.
Users that don't provide an email from that domain, will get an error.

= Can I use a JWT generated by another plugin to login? =
Yes. The only thing you have to make sure, in order to work, is that you use the same "Decryption Key" and encryption algorithm.

= Is the Auth Code required? =
No, it is not required. You can disable it from 'Login config', 'Register Config' and 'Delete User Config'. Just set the parameter 'Login|Register requires Auth Code' to 'No'.

= I don't want other users to be able to delete users. What should I do? =
The 'delete users option' is disabled by default. To make sure nobody will delete a user, please make sure the option "Allow Delete" is set to "No".

=Can I automatically log in to a WordPress website from my mobile App using this plugin?=
Yes. The main feature of this plugin is to automatically log in users into a WordPress website using a JWT. So, you can log in into WordPress from mobile apps, react native, angular, Vue js, meteor, backbone, javascript, etc.

= How to use hooks? =

Here is a code example, how to send an email after a new user has been created.

``
    add_action( 'simple_jwt_login_register_hook', function($user, $password){
   	    $to      = $user->user_email;
   	    $subject = 'Welcome';
   	    $message = '
                   Welcome to My Site. Your new user credentials are: 
                   email: ' . $to .'
                   password: '. $password;
   	    wp_mail($to, $subject, $message);
       }, 10, 2);
``

Here is an example on how you can overwrite the "No Redirect" response after autologin:
``
    add_filter('simple_jwt_login_no_redirect_message',function($response, $request){
        $response['userId'] = get_current_user_id();
        $response['userDetails'] = wp_get_current_user();
        return $response;
    },10, 2);
``

Here is an example, on how you can change the body for reset password email template:
``
    add_filter('simple_jwt_login_reset_password_custom_email_template',
    function ($template, $request) {
            $template .= 'The template has been modified by hook';
            return $template;
        },
        10,
        2
    );
``

= I cannot get the JWT from session. Where should I store the JWT? =
The plugin searches for the JWT in:
- URL ( &jwt=YOUR JWT HERE)
- SESSION (  ` $_SESSION['simple-jwt-login-token'] `)
- COOKIE ( ` $_COOKIE['simple-jwt-login-token'] ` )
- Header ( ` Authorization: Bearer YOUR_JWT_HERE `)

Also, the key name for each parameter, can be changed in the general section.

= I would like to create users with different roles. It is possible? =
Yes. In order to be able to create different users with different roles, first you have to create some AUTH Codes, and set the desired roles for each Auth Code.
After that, for the create user route, simply add the AUTH code in the request, and the role from 'Register User' will be overwritten with the one from Auth Code.

== Changelog ==

A complete Changelog is available on the [GitHub repository](https://github.com/nicumicle/simple-jwt-login/blob/master/Changelog.md).


= 3.5.6 ( 03 Aug 2024) =
- Update WordPress 6.6 Compatibility
- Fix revoked token validation when middleware enabled [#110](https://github.com/nicumicle/simple-jwt-login/issues/110)

= 3.5.5 ( 04 May 2024) =
- Update README
- Refactor Protect Endpoints

= 3.5.4 ( 03 May 2024) =
- Add OAuth support for Google [#97](https://github.com/nicumicle/simple-jwt-login/issues/97)
- Fix status code for expired tokens [#102](https://github.com/nicumicle/simple-jwt-login/issues/102)
- Update WordPress 6.5 Compatibility

= 3.5.3 (16 November 2023) =
- Fix licence in composer.json
- Update WordPress 6.4 compatibility

= 3.5.2 (02 November 2023) =
- Fix change user password with revoked JWT
- Change routes priority from floats to int and fix deprecation message "Implicit conversion from float to int loses precision"
- Add `iss` to JWT payload and allow to configure it
- Fix user meta on register user [#86](https://github.com/nicumicle/simple-jwt-login/issues/86)
- Fix calling protected endpoints with revoked token [#75](https://github.com/nicumicle/simple-jwt-login/issues/75)

= 3.5.1 (1 October 2023) =
- Update WordPress 6.3 compatibility

= 3.5.0 (04 Jan 2023) =
- Fix unable to create post issue when protect endpoints is enabled for all endpoints
- Search user by email on reset password
- Switch `get_user_by_email` to `get_user_by()` due to [deprecation](https://developer.wordpress.org/reference/functions/get_user_by_email/)
- Remove method `convertUserToArray` from WordPressData.
- Drop support for PHP 5.3 and PHP 5.4

= 3.4.10 (14 Dec 2022) =
- Fix issue with rest routes

= 3.4.9 (04 Dec 2022) =
- Add Strength indicator for JWT decryption key
- Allow setting custom length for random password. The default is 10 characters.
- Allow sending base_64 encoded `password` and `passhash` on the `/auth` endpoint
- Fix issue with `includeRequestParameters` that has been building incorrect URLs
- Add query parameters filter on autologin redirect
- Add the `simple_jwt_login_before_endpoint` hook before all simple-jwt-login routes are initialized

= 3.4.8 (04 Nov 2022) =
- Add filter to allow the change for authentication payload
- Change how we log in the user on while using the "protect endpoint" feature
- Refactor Route Service getUserFromJWT method
- Sanitize data from request
- Fix password issue when it contains special characters
- `/auth/validate` endpoint supports both `GET` and `POST` methods

= 3.4.7 (02 Oct 2022) =
- Remove unused code from the JWT library
- Move JWT Library to a folder
- Stay on current page after saving settings
- Some small text sanitizations
- Change how views are loaded in order to prevent "local file inclusion risk"

= 3.4.6 (27 Apr 2022) =
- Fix user_meta when passed as json in request body

= 3.4.5 (11 Apr 2022) =
- Add Redirect on Fail autologin
- Add shortcodes for displaying autologin errors

= 3.4.4 (03 Apr 2022) =
- Add hooks for all success responses

= 3.4.3 ( 30 Jan 2022) = 
- Tested with WordPress 5.9
- Do not add empty JWT to Authorization header

= 3.4.2 (14 Dec 2021) =
- Display user roles on auth/validate and on register user

= 3.4.1 (05 Dec 2021) =
- Fix protect endpoint conflict with wp-admin actions
- Check if user role exists
- Improve logic for protect endpoints
- Allow Authentication with DB hashed password
- Change user password with JWT

= 3.4.0 (17 Oct 2021) =
- Implement protected endpoints

= 3.3.1 (13 Oct 2021) =
- Sanitize load views

= 3.3.0 (13 Oct 2021) =
- Sanitize all displayed texts
- Add missing translation texts
- Update bootstrap libraries
- Update all translations
- Improve random password algorithm for better security

= 3.2.1 (09 Oct 2021) =
- Fix CSRF for admin settings

= 3.2.0 (26 Sept 2021) =
- Add user to simple_jwt_login_register_hook and simple_jwt_login_login_hook hooks
- Add option to allow adding a JWT in the register user endpoint

= 3.1.0 (31 July 2021) =
- Add reset password and change password endpoints

= 3.0.0 (11 July 2021) =
- Plugin code refactor
- Rewrite file auto-loaded
- Improve parse request parameters
- Add support for JSON body requests
- Fix user_meta URL encoded
- Add support for Force Login plugin
- Add Auth codes to dashboard
- Add IP limitation for Authentication
- Add support for Delete user by username
- Add support for `*` in IP restrictions
- Fix user role `None` when empty role in Auth Codes
- Add Auth code on Authentication endpoint

= 2.6.2 (29 April 2021) =
- Update documentation link with plugin website URL

= 2.6.1 (10 April 2021) =
- Add documentation link

= 2.6.0 (08 December 2020) =
- Add `No Redirect` option for autologin and respond with a json on this endpoint
- Add Hook for `No redirect` in order to customize the autologin response

= 2.5.2 (27 November 2020) =
- Add permission callback to api routes
- Use session start only when session token has been activated

= 2.5.1 (16 November 2020) =
- Fix Authorization header

= 2.5.0 (15 November 2020) =
- Add key change for URL, Session, Cookie and Header parameters

= 2.4.1 (21 October 2020) =
- Add more variables for `redirectUrl`

= 2.4.0 (20 October 2020) =
- Add `redirectUrl` parameter
- Add variables for URLs
- Fix session start warning

= 2.3.1 (01 September 2020) =
- Highlight Settings errors and display section
- fix PHP warning for session_start()

= 2.3.0 (25 August 2020) =
- Add support for revoke token: /auth/revoke
- Allow adding extra parameters in payload on /auth endpoint
- Add filter on /auth in order to allow payload modification
- Add support for user_meta on create user
- Allow users to set decryption key in WordPress PHP code
- Display number of active hooks on dashboard
- Improve error system from plugin settings

= 2.2.7 (05 August 2020) =
- Fix warning for "register_rest_route was called incorrectly"
- Fix getting JWT from header: ignore white spaces
- Allow users to store base64 encoded decryption keys and use them as decoded when needed

= 2.2.6 (20 July 2020) =
* Fix issue with saving JWT algorithm
* Allow usage of certificates in order to encode/decode JWT
* Add option to add username in JWT payload
* Users can authenticate with WordPress username for /auth endpoint

= 2.2.5 (18 July 2020) =
* Allow login by username ( user_login )
* beta: Allow users to access private endpoints via API with JWT

= 2.2.4 (13 July 2020) =
* Fix tabs visibility issue on some WordPress versions

= 2.2.3 (10 July 2020) =
* Add a toggle for all hooks
* Fix CORS issue

= 2.2.2 (09 July 2020) =
* Attach plugin version to js and css
* Change the path for js and css files
* Change the load order for the JS files

= 2.2.1 (08 July 2020) =
* Fix issue with bootstrap

= 2.2.0 (29 June 2020) =
* Add /auth/validate endpoint to validate tokens and get some details about the user that it is present in the JWT

= 2.1.1 (26 June 2020) =
* Fix error for auto-login after registering user

= 2.1.0 (20 June 2020) =
* Add support for CORS
* Include request parameters used for login link in the REDIRECT URL
* Add initial request data to the hook simple_jwt_login_redirect_hook call
* Add expiration date and user role to AUTH Codes

= 2.0.0 (14 June 2020) =
* New UI for plugin configuration
* Allow users to enable/disable specific hooks
* Add route for JWT generator
* Add route that refreshes an expired JWT
* Allow custom user_login for new users.
* Add WP_user in create user response

= 1.6.4 (06 June 2020) =
* Fix route PHP warning

= 1.6.3 (26 May 2020) =
* Add a hook that is called before the user it is redirected to the page he specified in the login section.

= 1.6.2 (23 May 2020) =
* Add plain text password to register user hook
* Update documentation
* Add option for a random password on new created users
* Add option 'Initialize force login after register' - that allows users to continue on the auto-login flow after user registration
* Add more options for create new user
* Add more options when a new user is created

= 1.6.1 (20 May 2020) =
* Improve mechanism for detecting if plugin needs update/create for DB option
* Add new option to get JWT from '$_COOKIE' and '$_SESSION'
* Update readme

= 1.6.0 (17 May 2020) =
* Fix save settings with minimum number of parameters ( No auth codes if all options are disabled)
* Add hooks for login, register and create User.
* Ignore case for JWT parameter
* JWT can be added in header
* Update Readme

= 1.5.0 (05 Feb 2020) =
* Allow delete users based on a JWT token
* Refactor routes section
* Allow users to set custom namespace for API route
* Change create user route name and offer support for backward compatibility

= 1.4.0 (29 Jan 2020) =
* Add codes to errors
* Code refactor
* Allow save in settings with no AUTH_KEYS when they are not used
* Improve sample URL generators
* Small UI Changes
* Fix validations
* Keep settings values even if there is an error
* Update readme

= 1.3.1 (20 Dec 2019) =
* Plugin can be configured only by administrators

= 1.3.0 (28 Nov 2019) =
* Add support for translations
* Code refactor

= 1.2.4 (26 Nov 2019) =
* Improve UI for Auth codes
* Update Readme

= 1.2.3 (16 Nov 2019) =
* Allow users to change Auth Key parameter

= 1.2.2 (16 Nov 2019) =
* Add support for getting key from jwt and array

= 1.2.1 (23 June 2019) =
* Add functionality for copy login and register example URL

= 1.2.0 (21 June 2019) =
* Allow login by email or WordPress user ID
* UI / UX small improvements

= 1.1.0 (15 June 2019) =
* Add support for IP address limitation for login / register
* Allow users to register only with emails from specific domains
* Possibility to make requests without Auth Codes

= 1.0.0 (14 June 2019) =
* Initial release