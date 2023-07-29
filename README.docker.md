# New features
fcms is automatically setup from your .env file.
Build it using the docker/.env.example file

# Building a docker image
Run `docker build -t fcms`

# Running the image
If you built your own image, no need to change the docker-compose file, otherwise change the name:tag of the image to pick it from a repo (ie  leolivier/fcms:experimental).
Run `docker compose up -d`
This will download the images for nginx, mariadb, and use the previously built image of fcms or download the image from the repo
and start them.
Go to http://<your host>:8003 (if you didn't change the defaultport in .env)
Enjoy...
