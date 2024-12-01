# streaming-plus
Open Source Streaming Media Center


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



Tabelle DB

Sources
- source_id
- source_class
- source_name
- source_url

Providers
- provider_id
- provider_class
- provider_name (Es. Netflix)
- provider_imdb_id
- provider_jw_id

Movies
- movie_id
- movie_imdb_id
- movie_tmdb_id
- movie_jw_id
- movie_sc_id
- movie_title
- movie_original_title
- movie_year
- movie_release_date
- movie_duration
- movie_rating
- movie_min_age

//NOTA: per il momento non mi servono tabelle per gestire le immagini perché vorrei farlo con Jellyfin.