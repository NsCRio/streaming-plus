# streaming-plus [DEMO]
Open Source Streaming Media Center

Jellyfin Media Center + Streamio Addons


Build
docker-compose build --no-cache ${CONTAINERS}

Run + Build
docker-compose up -d --build

Run
docker run --name StreamingPlus -e PUID=1000 -e GUID=1000 -e TIMEZONE=Europe/Rome -v ./data:/data -p 8096:8096 -d nscrio/streaming-plus:latest 
docker-compose up -d

Shell
docker-compose exec app bash

Down
docker-compose down

#Se da errore apt-get update
docker-compose down -v --rmi all --remove-orphans
