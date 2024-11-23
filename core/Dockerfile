FROM --platform=linux/amd64 python:slim-bullseye

# Aggiornamento librerie
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
    wget \
    zip \
    curl \
    unzip \
    git \
    build-essential

# Installa le dipendenze
RUN pip install Flask

# Imposta la directory di lavoro
WORKDIR /var/core

# Copia i file necessari
COPY ./core /var/core

#Avvio dello script
CMD ["python3", "/var/core/core.py"]

# Espone la porta 9000
EXPOSE 5092