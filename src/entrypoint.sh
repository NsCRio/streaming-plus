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

#MOMENTANEO PER DEV, Copio i file direttamente dalla cartella /var/src
cp /var/src/php.ini $PHP_INI_DIR/conf.d/
cp /var/src/opcache.ini $PHP_INI_DIR/conf.d/
cp /var/src/supervisord.conf /etc/supervisor/supervisord.conf
cp /var/src/entrypoint.sh /usr/local/bin/entrypoint.sh
cp /var/src/nginx.conf /etc/nginx/nginx.conf
cp /var/src/default.conf /etc/nginx/conf.d/
#cp -r /usr/share/jellyfin/web /var/src/jellyfin-web

#Creo le directory che mi servono
mkdir -p $SP_DATA_PATH/app
mkdir -p $SP_DATA_PATH/app/sessions
mkdir -p $SP_DATA_PATH/jellyfin
mkdir -p $SP_DATA_PATH/jellyfin/cache
mkdir -p $SP_DATA_PATH/jellyfin/config
mkdir -p $SP_DATA_PATH/library
#mkdir -p $SP_DATA_PATH/env

#Configurazione Laravel
if [ ! -f $SP_DATA_PATH/app/database.sqlite ]; then
  cp /var/www/database/database.sqlite $SP_DATA_PATH/app/database.sqlite
fi
php /var/www/artisan migrate

#Customizzazioni Jellyfin
if [ -d /usr/share/jellyfin/web ]; then
  cp -r /var/src/img/* /usr/share/jellyfin/web/assets/img
  cp /var/src/jellyfin/config/network.xml $SP_DATA_PATH/jellyfin/config/network.xml
  if [ ! -f $SP_DATA_PATH/jellyfin/config/branding.xml ]; then
    cp /var/src/jellyfin/config/branding.xml $SP_DATA_PATH/jellyfin/config/branding.xml
  fi
fi

#Cambio i permessi nelle cartelle data
chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/app
chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/jellyfin
chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/library

#Crea l'environment di python
#python3 -m venv $SP_DATA_PATH/env

#Installa StreamingCommunity
#$SP_DATA_PATH/env/bin/pip install --upgrade StreamingCommunity

#Installa MammaMia
#if [ -d $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia ]; then
#  rm -r $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia
#fi
#git clone https://github.com/UrloMythus/MammaMia.git $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia
#$SP_DATA_PATH/env/bin/pip install -r $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia/requirements.txt
#if [ ! -f $DOCUMENT_ROOT/config.json ]; then
#  cp $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia/config.json $DOCUMENT_ROOT/config.json
#fi


echo ""
echo "***********************************************************"
echo " Starting Streaming-Plus Services                          "
echo "***********************************************************"

## Start Supervisord
supervisord -c /etc/supervisor/supervisord.conf