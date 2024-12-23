# Streaming Plus (Beta)
## Open Source Streaming Media Center
### Jellyfin Media Center + Streamio Addons

---

## Installation

### With docker run

```
docker run --name StreamingPlus -e PUID=1000 -e GUID=1000 -e TZ=Europe/Rome -v ./data:/data -p 8096:8096 -d nscrio/streaming-plus:latest
```

### With docker compose 

```
version: '3'
services:
  streaming_plus:
    image: nscrio/streaming-plus:latest
    container_name: streaming_plus
    restart: unless-stopped
    environment:
      - PUID=1000
      - GUID=1000
      - TZ=Europe/Rome
    volumes:
      - ./data:/data
    ports:
      - "8096:8096"
```

