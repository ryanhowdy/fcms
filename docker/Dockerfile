FROM php:apache

# Arguments defined in docker-compose.yml
ARG user=fcms
ARG uid=1000

# install helper for installing php extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
# install php extensions
RUN chmod +x /usr/local/bin/install-php-extensions && \
   install-php-extensions @composer pdo_mysql exif pcntl gd imagick

# Set working directory
WORKDIR /var/www

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user
# Copy the fcms code 
COPY --chown=$user:www-data . .
RUN \
    # create apache site
    mv docker/apache/fcms.conf /etc/apache2/sites-available/ && \
    a2ensite fcms && \
    a2enmod rewrite negotiation && \
    # move the right init file (which exec apache2-foreground at the end)
    mv docker/apache/init.sh ./docker_init.sh && \
    # clean unused folders and blocking files
    rm -rf docker vendor composer.lock && \
    # Install everything
    composer install --no-dev   

# Set user
USER $user

HEALTHCHECK --interval=5m --timeout=3s --start-period=10s \
  CMD curl -f http://localhost/ || exit 1

CMD ["./docker_init.sh"]