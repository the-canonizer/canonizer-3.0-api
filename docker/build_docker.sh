# This command is used to build the docker image.
# DO NOT USE THIS TO RUN THE DOCKER
# INSTEAD USE docker-compose --env-file .env up
docker-compose --env-file .env up --build --remove-orphans --force-recreate
