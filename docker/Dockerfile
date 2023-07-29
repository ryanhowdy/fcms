FROM php:8.2-fpm

# Arguments defined in docker-compose.yml
ARG user=fcms
ARG uid=1000

# Install system dependencies
#RUN apt-get update && apt-get install -y git curl
# Clear cache
#RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# install helper for installing php extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
# install php extensions
RUN chmod +x /usr/local/bin/install-php-extensions && \
   install-php-extensions @composer pdo_mysql exif pcntl gd imagick

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user
# Set working directory
WORKDIR /var/www
# Copy the fcms code (--chown=$user doesn't work with Docker Desktop)
COPY . .
RUN chown -R $user . && \
    mv docker/.env . && \
    mv docker/init.sh ./docker_init.sh && \
    rm -rf docker 

# Set user
USER $user
# Install everything
RUN rm -rf vendor composer.lock && \
    composer install && \
    php artisan key:generate

HEALTHCHECK --interval=5m --timeout=3s --start-period=10s \
  CMD curl -f http://localhost/ || exit 1

CMD ["./docker_init.sh"]