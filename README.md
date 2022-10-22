<p align="center">
    <img src="https://ps.w.org/simple-jwt-login/assets/banner-772x250.png?rev=2106097" alt="Banner"/>
</p>

<p align="center">
   <img src="https://img.shields.io/wordpress/plugin/stars/simple-jwt-login" alt="Rating" />
   <img src="https://img.shields.io/wordpress/plugin/dt/simple-jwt-login" alt="Total Downloads" />
   <img src="https://img.shields.io/wordpress/plugin/installs/simple-jwt-login" alt="Active installs" />
</p>
<p align="center">
   <img src="https://img.shields.io/github/contributors/nicumicle/simple-jwt-login" alt="Contributors" />
   <img src="https://img.shields.io/github/last-commit/nicumicle/simple-jwt-login" alt="Last Commit"/>
   <img src="https://img.shields.io/github/issues-raw/nicumicle/simple-jwt-login" alt="Open issues"/>
   <img src="https://img.shields.io/github/issues-closed-raw/nicumicle/simple-jwt-login" alt="Closed issues"/>
   <img src="https://img.shields.io/github/issues-pr/nicumicle/simple-jwt-login" alt="Open pull requests" />
   <img src="https://img.shields.io/github/issues-pr-closed/nicumicle/simple-jwt-login" alt="Closed pull requests" />
</p>
<p align="center">
    <img src="https://img.shields.io/wordpress/plugin/v/simple-jwt-login" alt="Simple-Jwt-Login WordPress.org version"/>
    <img src="https://img.shields.io/wordpress/plugin/required-php/simple-jwt-login" alt="Required PHP version"/>
    <img src="https://img.shields.io/wordpress/plugin/tested/simple-jwt-login" alt="Latest Tested WordPress version"/>
</p>
<p align="center">
    <img src="https://img.shields.io/github/v/tag/nicumicle/simple-jwt-login" alt="Current Tag" />
    <img src="https://github.com/nicumicle/simple-jwt-login/actions/workflows/php.yml/badge.svg" alt="Check plugin" />
    <img src="https://codecov.io/gh/nicumicle/simple-jwt-login/branch/master/graph/badge.svg?token=dVOwuGQoY3" alt="Coverage"/>
    <img src="https://img.shields.io/github/license/nicumicle/simple-jwt-login" alt="License" />
</p>

<p align="center">
    <b>Simple JWT Login</b> is a <b>free</b> WordPress plugin that allows you to use a JWT on WordPress REST endpoints.
</p>
<p align="center">
    The main purpose of this plugin is to allow Mobile apps, or other websites to access the content from a WordPress website via REST endpoints in a secure way.
</p>

## Overview
<p align="center">
    <img src="https://github.com/nicumicle/simple-jwt-login/blob/master/wordpress.org/assets/schema.png?raw=true" alt="Simple-JWT-Login schema" />
</p>

Table of contents
=================

<!--ts-->
* [Installation](#bulb-installation)
  * [Install from Zip](#install-from-zip)
  * [Install from WordPress.org](#install-from-wordpressorg)
* [Features](#tada-features)
* [Integrate](#electric_plug-integrate)
  * [PHP SDK](#php-sdk)
* [Documentation](#ledger-documentation)
* [Roadmap](#rocket-roadmap)
* [Contribute](#scroll-contribute)
  * [How can you contribute](#how-can-you-contribute)
* [Contributors](#trophy-contributors)

<!--te-->

## :bulb: Installation

Please note that this plugin version is not fully tested.

If you want to make sure you have a stable version, please download this plugin from [WordPress.org](https://wordpress.org/plugins/simple-jwt-login/).

| :warning: Make sure you use the latest plugin version in production. |
| --- |

### Install from Zip

If you want to upload the simple-jwt-login plugin to your website:
- Download [downloads/simple-jwt-login.zip](https://github.com/nicumicle/simple-jwt-login/blob/master/download/simple-jwt-login.zip)
- Upload the zip file into your WordPress website
- Activate the plugin

### Install from WordPress.org

In order to install the latest stable version, from your WordPress admin:
- Go to the ‘Plugins’ menu in WordPress and click ‘Add New’
- Search for ‘Simple JWT Login’ and select ‘Install Now’
- Activate the plugin when prompted

## :tada: Features 

- **Authenticate** : REST endpoint that will generate/validate/revoke a JWT
- **Autologin**: Autologin to a WordPress website with JWT
- **Register user**: Register users in WordPress by calling a REST endpoint
- **Delete user**: You can delete a WordPress user by adding some details in the JWT payload.
- **Reset password**: REST endpoint that allows you to reset WordPress User password. Also, it can send custom email if you want.
- **Protect endpoints**: Protect WordPress endpoints with a JWT. This way, you can make some endpoints private, and the content can be viewed only if you provide a valid JWT.
- **Allow JWT usage on other endpoints**: Add a JWT to requests for other API endpoints and you will act as an authenticated user.
- **Integrate with other plugins**: This plugin works well in combination with other plugins that extends the WordPress REST API.

## :electric_plug: Integrate

### PHP SDK

In order to easily integrate your app/site with the simple-jwt-login plugin, we have developed a composer package.

You can check this [GitHub repository](https://github.com/nicumicle/simple-jwt-login-client-php) for more details and code examples.


## :ledger: Documentation

Plugin documentation is available at [simplejwtlogin.com](https://simplejwtlogin.com).

- [Introduction](https://simplejwtlogin.com/docs/)<br>
- [Authentication](https://simplejwtlogin.com/docs/authentication)<br>
- [Autologin](https://simplejwtlogin.com/docs/autologin)<br>
- [Register User](https://simplejwtlogin.com/docs/register-user)<br>
- [Reset Password](https://simplejwtlogin.com/docs/reset-password)<br>
- [Delete User](https://simplejwtlogin.com/docs/delete-user)<br>
- [Protect Endpoints](https://simplejwtlogin.com/docs/protect-endpoints)<br>
- [Hooks](https://simplejwtlogin.com/docs/hooks)


## :rocket: Roadmap

Check out the [roadmap](https://github.com/users/nicumicle/projects/1) to get informed on the latest released features, current statuses, and upcoming features.

## :scroll: Contribute

Simple-JTW-Login is an open-source project and welcomes all contributors.

As with all WordPress projects, we want to ensure a welcoming environment for everyone. 

With that in mind, all contributors are expected to follow our [Code of Conduct](https://github.com/nicumicle/simple-jwt-login/blob/master/CODE_OF_CONDUCT.md).

### How can you contribute:

- Open Merge requests on existing issues: [CONTRIBUTING.md](https://github.com/nicumicle/simple-jwt-login/blob/master/CONTRIBUTING.md)
- Suggest features or report bugs: [issues/bugs](https://github.com/nicumicle/simple-jwt-login/issues/new/choose)
- Translate the plugin: [https://translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/simple-jwt-login/)

## :trophy: Contributors
Thanks to all our contributors!

<a href="https://github.com/nicumicle/simple-jwt-login/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=nicumicle/simple-jwt-login" />
</a>

## Copyright

This project is distributed under the [GNU General Public License v3.0](https://github.com/nicumicle/simple-jwt-login/blob/master/LICENSE).

By submitting a pull request to this project, you agree to license your contribution under the GNU General Public License v3.0 to this project.
