#!/bin/sh

echo ""
echo "***********************************************************"
echo " Starting Streaming-Plus Docker Container                   "
echo "***********************************************************"

set -e
set -e
info() {
    { set +x; } 2> /dev/null
    echo '[INFO] ' "$@"
}
warning() {
    { set +x; } 2> /dev/null
    echo '[WARNING] ' "$@"
}
fatal() {
    { set +x; } 2> /dev/null
    echo '[ERROR] ' "$@" >&2
    exit 1
}

#Creo le directory che mi servono
info "-- Creating the necessary folders if they do not already exist"
mkdir -p $SP_DATA_PATH/app
mkdir -p $SP_DATA_PATH/app/sessions
mkdir -p $SP_DATA_PATH/jellyfin
mkdir -p $SP_DATA_PATH/jellyfin/cache
mkdir -p $SP_DATA_PATH/jellyfin/config
mkdir -p $SP_DATA_PATH/library

#Configurazione Laravel
info "-- Configuring the basic dependencies of the app"
if [ ! -f $SP_DATA_PATH/app/database.sqlite ]; then
  cp /var/www/database/database.sqlite $SP_DATA_PATH/app/database.sqlite
fi
composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate

#Customizzazioni Jellyfin
info "-- Performing customizations to Jellyfin"
if [ -d /usr/share/jellyfin/web ]; then
  cp -r /var/src/img/* /usr/share/jellyfin/web/assets/img
  cp /var/src/jellyfin/config/network.xml $SP_DATA_PATH/jellyfin/config/network.xml
  if [ ! -f $SP_DATA_PATH/jellyfin/config/branding.xml ]; then
    cp /var/src/jellyfin/config/branding.xml $SP_DATA_PATH/jellyfin/config/branding.xml
  fi
fi

#Cambio i permessi nelle cartelle data
info "-- Changing permissions to folders"
chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/app
chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/jellyfin
chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/library


echo ""
echo "***********************************************************"
echo " Starting Streaming-Plus Services                          "
echo "***********************************************************"

## Start Supervisord
supervisord -c /etc/supervisor/supervisord.conf