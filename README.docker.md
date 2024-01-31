# New features
fcms is automatically setup from your .env file.
Build it using the docker/.env.example file

# Building a docker image
Run `docker build -t fcms .`
Or for multiple targets: `docker buildx build -t fcms --platform linux/arm64,linux/amd64 --push .`

# Running the image
Copy the docker/docker-compose.yml file in a new directory, create in this directory a .env file based on the .env.docker.example file.
If you built your own image, change the docker-compose file to use this image, otherwise change the name:tag of the image to pick it from a repo (ie  ghcr.io/leolivier/fcms:artisan).
Run `docker compose up -d` in this directory
This will download the image for mariadb, and use the previously built image of fcms or download the image from the repo and start them.
Go to http://<your host>:8003 (if you didn't change the defaultport in .env)
Enjoy...
