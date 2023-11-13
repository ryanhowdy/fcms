FROM php:8.2-fpm-alpine

# Arguments defined in docker-compose.yml
ARG user=fcms
ARG uid=1000

# install helper for installing php extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
# install php extensions
RUN chmod +x /usr/local/bin/install-php-extensions && \
   install-php-extensions @composer pdo_mysql exif pcntl gd imagick

# Set working directory
WORKDIR /fcms

# Create system user to run Composer and Artisan Commands
RUN addgroup -S $user && \
    adduser -G $user -G www-data -G root -u $uid -h /home/$user -S $user && \
    mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user
# Copy the fcms code
COPY --chown=$user:www-data . .
RUN \
    # clean unused folders and locking files
    rm -rf vendor composer.lock .git tests && \
    # Install everything
    composer install --no-dev

# Set user
USER $user

HEALTHCHECK --interval=5m --timeout=3s --start-period=10s \
  CMD curl -f http://localhost/ || exit 1

ENTRYPOINT [ "./docker_init.sh" ]
CMD [ "php", "artisan", "serve", "--host=0.0.0.0", "--port", "8000" ]
EXPOSE 8000
