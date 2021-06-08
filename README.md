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
Welcome to the Simple-JWT-Login repository on GitHub. Here you can browse the source, look at open issues and keep track of development.
</p>

If you are not a developer, please use the [Simple-JWT-Login plugin page](https://wordpress.org/plugins/simple-jwt-login/) on WordPress.org.

## Contributing to Simple-JWT-Login
Simple-JTW-Login is an open-source project and welcomes all contributors.

As with all WordPress projects, we want to ensure a welcoming environment for everyone. With that in mind, all contributors are expected to follow our [Code of Conduct](https://github.com/nicumicle/simple-jwt-login/blob/master/CODE_OF_CONDUCT.md).

## Development

### Dev Installation

Clone this repository in your WordPress `/wp-content/plugins` folder.

After that, run:
```
    composer install
```

Plugin code in is the folder `simple-jwt-login`.

### Install on a WordPress Website

If you want to upload the simple-jwt-login plugin to your website:
- clone this repository 
- create a zip folder for the `simple-jwt-login` folder 
- upload the zip file into your WordPress
 
### Running tests

```
    vendor/bin/phpunit tests/
```

### Coding Standards

```
    vendor/bin/phpcs simple-jwt-login/src
```

### Check plugin
This will check the plugin build, and it will run php-md, php-cs and the phpunit tests.

```
    composer check-plugin
```


 
