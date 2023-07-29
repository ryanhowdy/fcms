# Building a docker image
Run `docker build -t fcms`

# Running the image
Run `docker compose up -d`
This will upload the images for nginx, mariadb, and use the previously built image of fcms
The you must init fcms by runnning the commands:
```
$ docker compose exec -it fcms-app rm composer.lock
$ docker compose exec -it fcms-app composer install
$ docker compose exec -it fcms-app php artisan key:generate
$ docker compose exec -it fcms-app php artisan migrate
```

# Next to come
Automate most init during build and start phases.
