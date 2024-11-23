FROM php:8.2-fpm

# Installa le dipendenze di sistema per PHP e estensioni
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    unzip \
    git \
    python3 \
    python3-pip \
    nodejs  \
    npm \
&& docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installa Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Imposta la variabile d'ambiente per Composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Imposta la directory di lavoro
WORKDIR /var/www

# Copia l'applicazione Laravel e i file necessari
COPY ../www /var/www
COPY ../src /var/src

# Installa le dipendenze di Laravel tramite Composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

#RUN npm install && npm run dev

# Imposta i permessi delle cartelle
RUN chown -R www-data:www-data /var/www

#Gestisco il file .env
COPY ../.env /var/www/.env.global
RUN cp .env.global .env

# Espone la porta 9000
EXPOSE 9000