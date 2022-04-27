<p align="center">
    <img src="https://ps.w.org/simple-jwt-login/assets/banner-772x250.png?rev=2106097">
</p>

<p align="center">

   <img src="https://img.shields.io/wordpress/plugin/stars/simple-jwt-login" alt="Rating" />
   <img src="https://img.shields.io/wordpress/plugin/dt/simple-jwt-login" alt="Total Downloads" />
   <img src="https://img.shields.io/wordpress/plugin/installs/simple-jwt-login" alt="Active installs" />
</p>
<p align="center">
    <img src="https://github.com/nicumicle/simple-jwt-login/actions/workflows/php.yml/badge.svg" />
    <img src="https://codecov.io/gh/nicumicle/simple-jwt-login/branch/master/graph/badge.svg?token=dVOwuGQoY3"/>
</p>

<p align="center">
    Simple JWT Login is a WordPress plugin that allows you to use a JWT on WordPress REST endpoints.
</p>
<p align="center">
    The main purpose of this plugin is to allow Mobile apps, or other websites to access the content from a WordPress website via REST endpoints in a secure way.
</p>

## Main features of the plugin

- Authenticate : REST endpoint that will generate a JWT
- Autologin: Autologin to a WordPress website with JWT
- Register user: Register users in WordPress by calling a REST endpoint
- Delete user: You can delete a WordPress user by adding some details in the JWT payload.
- Reset password: REST endpoint that allows you to reset WordPress User password. Also, it can send custom email if you want.
- Protect endpoints: Protect WordPress endpoints with a JWT. This way, you can make some endpoints private, and the content can be viewed only if you provide a valid JWT.

## Documentation

Plugin documentation can be found on [simplejwtlogin.com](https://simplejwtlogin.com).

### Install on a WordPress Website

Please note that this plugin version is not fully tested.

If you want to make sure you have a stable version, please download this plugin from [WordPress.org](https://wordpress.org/plugins/simple-jwt-login/).

| :warning: Make sure you use the latest plugin version in production. |
| --- |

If you want to upload the simple-jwt-login plugin to your website:
- Download [downloads/simple-jwt-login.zip](https://github.com/nicumicle/simple-jwt-login/blob/master/download/simple-jwt-login.zip)
- Upload the zip file into your WordPress website
- Activate the plugin

## Contributing to Simple-JWT-Login

Simple-JTW-Login is an open-source project and welcomes all contributors.

As with all WordPress projects, we want to ensure a welcoming environment for everyone. 

With that in mind, all contributors are expected to follow our [Code of Conduct](https://github.com/nicumicle/simple-jwt-login/blob/master/CODE_OF_CONDUCT.md).

### Contribute 

- Open Merge requests on existing issues: [contribute](https://github.com/nicumicle/simple-jwt-login/blob/master/CONTRIBUTING.md)
- Suggest features or report bugs: [issues/bugs](https://github.com/nicumicle/simple-jwt-login/issues/new/choose)
- Translate the plugin: [https://translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/simple-jwt-login/)

## PHP Client

In order to easily integrate your app/site with the simple-jwt-login plugin, we have developed a composer package.

You can check the [github repository](https://github.com/nicumicle/simple-jwt-login-client-php) for more details and code examples.
