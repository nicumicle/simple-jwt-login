FROM wordpress:php8.3

RUN apt-get update

RUN apt-get -y install \
    vim \
    git

# Install wp-cli
RUN curl -o /bin/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN chmod +x /bin/wp-cli.phar
RUN cd /bin && mv wp-cli.phar wp

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install and configure xDebug
RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.profiler_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.profiler_output_name=cachegrind.out.%p_%R" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=develop,debug,profile,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && rm -rf /usr/local/etc/php/conf.d/opcache-recommended.ini

# PHP ini changes
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# PHP error logging configuration (overrides php.ini via conf.d)
RUN mkdir -p /var/log/php && chmod 777 /var/log/php && \
    printf '[PHP]\n\
memory_limit = 128M\n\
error_reporting = E_ALL\n\
display_errors = On\n\
display_startup_errors = On\n\
log_errors = On\n\
error_log = /var/log/php/error.log\n\
' > /usr/local/etc/php/conf.d/99-error-logging.ini
