# Changelog - Releases

## 3.4.7 (02 October 2022)
- Remove code vulnerability from the JWT library
- Stay on current page after saving settings
- Some small text sanitizations
- Add "roave/security-advisories" to composer, in order to detect used packages vulnerabilities
- Change how views are loaded in order to prevent "local file inclusion risk"
- Add more rules in phpstan

## 3.4.6 (27 April 2022)
- Fix user_meta when passed as json in request body

## 3.4.5 (11 April 2022)
- Add Redirect on Fail autologin
- Add shortcodes for displaying autologin errors
- Add xdebug to docker

## 3.4.4 (03 April 2022)
- Add openapi file
- Add hooks for all success responses

## 3.4.3 (30 January 2022)
- Tested with WordPress 5.9
- Do not add empty JWT to Authorization header

## 3.4.2 (14 December 2021)

- Display user roles on auth/validate and on register user

## 3.4.1 (05 December 2021)

- Fix protect endpoint conflict with wp-admin actions
- Check if user role exists
- Improve logic for protect endpoints
- Allow Authentication with DB hashed password
- Change user password with JWT

## 3.4.0 (26 October 2021)

- Implement protected endpoints
- Improve code coverage

## 3.3.1 (13 October 2021)
- Sanitize load views

## 3.3.0 (13 October 2021)
- Sanitize all displayed texts
- Add missing translation texts
- Update bootstrap libraries
- Update all translations
- Improve random password algorithm for better security

## 3.2.1 (09 October 2021)
- Fix CSRF for admin settings

## 3.2.0 (26 September 2021)
- \#10: Add user to simple_jwt_login_register_hook and simple_jwt_login_login_hook hooks 
-  \#9: Add option to allow adding a JWT in the register user endpoint

## 3.1.0 (31 July 2021)
- Fix Auth Codes title on Authentication page
- Add Reset password and Send Reset password endpoints

##  3.0.0 (11 July 2021)
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

## 2.6.2 (29 April 2021)
- Update documentation link with the plugin website URL

## 2.6.1 (10 April 2021)
- Add documentation link

## 2.6.0 (08 December 2020)
- Add `No Redirect` option for autologin and respond with a json on this endpoint
- Add Hook for `No redirect` in order to customize the autologin response

## 2.5.2 (27 November 2020)
- Add permission callback to api routes
- Use session start only when session token has been activated

## 2.5.1 (16 November 2020)
- Fix Authorization header

## 2.5.0 (15 November 2020)
- Add key change for URL, Session, Cookie and Header parameters

## 2.4.1 (21 November 2020)
- Add more variables for `redirectUrl`

## 2.4.0 (20 October 2020)
- Add `redirectUrl` parameter
- Add variables for URLs
- Fix session start warning

## 2.3.1 (01 September 2020)
- Highlight Settings errors and display section
- fix PHP warning for session_start()

## 2.3.0 (25 August 2020)
- Add support for revoke token: /auth/revoke
- Allow adding extra parameters in payload on /auth endpoint
- Add filter on /auth in order to allow payload modification
- Add support for user_meta on create user
- Allow users to set decryption key in WordPress PHP code
- Display number of active hooks on dashboard
- Improve error system from plugin settings

## 2.2.7 (05 August 2020)
- Fix warning for "register_rest_route was called incorrectly"
- Fix getting JWT from the header: ignore white spaces
- Allow users to store base64 encoded decryption keys and use them as decoded when needed

## 2.2.6 (20 July 2020)
- Fix issue with saving JWT algorithm
- Allow usage of certificates in order to encode/decode JWT
- Add option to add username in JWT payload
- Users can authenticate with WordPress username for /auth endpoint

## 2.2.5 (18 July 2020)
- Allow login by username ( user_login )
- beta: Allow users to access private endpoints via API with JWT

## 2.2.4 (13 July 2020)
- Fix tabs visibility issue on some WordPress versions

## 2.2.3 (10 July 2020)
- Add a toggle for all hooks
- Fix CORS issue

## 2.2.2 (09 July 2020)
- Attach plugin version to js and css
- Change the path for js and css files
- Change the load order for the JS files

## 2.2.1 (08 July 2020)
- Fix issue with bootstrap

## 2.2.0 (29 June 2020)
- Add /auth/validate endpoint to validate tokens and get some details about the user that it is present in the JWT

## 2.1.1 (26 June 2020)
- Fix error for auto-login after registering user

## 2.1.0 (20 June 2020)
- Add support for CORS
- Include request parameters used for login link in the REDIRECT URL
- Add initial request data to the hook simple_jwt_login_redirect_hook call
- Add expiration date and user role to AUTH Codes

## 2.0.0 (14 June 2020)
- New UI for plugin configuration
- Allow users to enable/disable specific hooks
- Add route for JWT generator
- Add route that refreshes an expired JWT
- Allow custom user_login for new users.
- Add WP_user in create user response

## 1.6.4 (06 June 2020)
- Fix route PHP warning

## 1.6.3 (26 May 2020)
- Add a hook that is called before the user it is redirected to the page he specified in the login section.

## 1.6.2 (23 May 2020)
- Add plain text password to register user hook
- Update documentation
- Add option for a random password on new created users
- Add option 'Initialize force login after register' - that allows users to continue on the auto-login flow after user registration
- Add more options for create new user
- Add more options when a new user is created

## 1.6.1 (20 May 2020)
- Improve mechanism for detecting if plugin needs update/create for DB option
- Add new option to get JWT from '$_COOKIE' and '$_SESSION'
- Update readme


## 1.6.0 (17 May 2020)
- Fix save settings with minimum number of parameters ( No auth codes if all options are disabled)
- Add hooks for login, register and create User.
- Ignore case for JWT parameter
- JWT can be added in header
- Update Readme


## 1.5.0 (05 February 2020)
- Allow delete users based on a JWT token
- Refactor routes section
- Allow users to set custom namespace for API route
- Change create user route name and offer support for backward compatibility

## 1.4.0 (29 January 2020)
- Add codes to errors
- Code refactor
- Allow save in settings with no AUTH_KEYS when they are not used
- Improve sample URL generators
- Small UI Changes
- Fix validations
- Keep settings values even if there is an error
- Update readme

## 1.3.1 (20 December 2019)
- Plugin can be configured only by administrators

## 1.3.0 (28 November 2019)
- Add support for translations
- Code refactor

## 1.2.4 (26 November 2019)
- Improve UI for Auth codes
- Update Readme

## 1.2.3 (16 November 2019)
- Allow users to change Auth Key parameter

## 1.2.2 (16 November 2019)
- Add support for getting key from jwt and array

## 1.2.1 (23 June 2019)
- Add functionality for copy login and register example URL

## 1.2.0 (21 June 2019)
- Allow login by email or WordPress user ID
- UI / UX small improvements

## 1.1.0 (15 June 2019)
- Add support for IP address limitation for login / register
- Allow users to register only with emails from specific domains
- Possibility to make requests without Auth Codes

## 1.0.0 (14 June 2019)
- Initial release
