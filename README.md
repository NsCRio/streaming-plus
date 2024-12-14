# streaming-plus
Open Source Streaming Media Center
Jellyfin Media Center + Streamio Addons


Build
docker-compose build --no-cache ${CONTAINERS}

Run + Build
docker-compose up -d --build

Run
docker-compose up -d

Shell
docker-compose exec app bash

Down
docker-compose down

#Se da errore apt-get update
docker-compose down -v --rmi all --remove-orphans
