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

<p>
    Simple JWT Login is a WordPress plugin that allows you to use a JWT on WordPress REST endpoints.
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

## What version should you use

If you want to use this plugin in production, we recommend you to use the version from  [WordPress.org Simple-JWT-Login plugin page](https://wordpress.org/plugins/simple-jwt-login/).

| :warning: Make sure you use the latest plugin version in production. |
| --- |

In case you want to use the development version, you can download it from [downloads/simple-jwt-login.zip](https://github.com/nicumicle/simple-jwt-login/blob/master/download/simple-jwt-login.zip)


## Contributing to Simple-JWT-Login

Simple-JTW-Login is an open-source project and welcomes all contributors.

As with all WordPress projects, we want to ensure a welcoming environment for everyone. 

With that in mind, all contributors are expected to follow our [Code of Conduct](https://github.com/nicumicle/simple-jwt-login/blob/master/CODE_OF_CONDUCT.md).

You can contribute by: 
- Opening Merge requests on existing issues
- Suggest features
- Report bugs
- Write tests
- Help us to translate the plugin 

## PHP Client

In order to easily integrate your app/site with the simple-jwt-login plugin, we have developed a composer package.

You can install it in your app using:
```
    composer require nicumicle/simple-jwt-login-client-php
```

You can check the [github repository](https://github.com/nicumicle/simple-jwt-login-client-php) for more details and code examples.

## Development


### Dev Installation

Clone this repository.

After that, run:
```
    composer install
```

Plugin code in is the folder `simple-jwt-login`.

### Docker image
You can use docker, to set up this project on your local machine

```
 docker-compose -f docker/docker-compose.yaml up
```

After docker machine is up and running, you need to configure your local WordPress, by accessing the following URL in your browser :

```
http://localhost:88/
```

After that, you just need to activate the plugin.

### Running tests

```
    composer tests
```

### Coding Standards

```
    composer phpcs
```

### Check plugin
This will check the plugin build, and it will run php-md, php-cs and the phpunit tests.

```
    composer check-plugin
```


### Install on a WordPress Website

Please note that this plugin version is not fully tested.
If you want to make sure you have a stable version, please download this plugin from [WordPress.org](https://wordpress.org/plugins/simple-jwt-login/).

If you want to upload the simple-jwt-login plugin to your website:
- Download [downloads/simple-jwt-login.zip](https://github.com/nicumicle/simple-jwt-login/blob/master/download/simple-jwt-login.zip)
- Upload the zip file into your WordPress website
- Activate the plugin



 
