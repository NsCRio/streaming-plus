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

#Creo le directory che mi servono
mkdir -p $SP_DATA_PATH/app
mkdir -p $SP_DATA_PATH/jellyfin
mkdir -p $SP_DATA_PATH/jellyfin/cache
mkdir -p $SP_DATA_PATH/media
mkdir -p $SP_DATA_PATH/env

#Cambio i loghi di Jellyfin
cp -r /var/src/img/ /usr/share/jellyfin/web/assets/

#Cambio i permessi nelle cartelle data
chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/app
chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/jellyfin
chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/media

#Crea l'environment di python
python3 -m venv $SP_DATA_PATH/env

#Installa StreamingCommunity
$SP_DATA_PATH/env/bin/pip install --upgrade StreamingCommunity

#Installa MammaMia
if [ -d $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia ]; then
  rm -r $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia
fi
git clone https://github.com/UrloMythus/MammaMia.git $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia
$SP_DATA_PATH/env/bin/pip install -r $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia/requirements.txt
if [ ! -f $DOCUMENT_ROOT/config.json ]; then
  cp $SP_DATA_PATH/env/lib/python3.11/site-packages/MammaMia/config.json $DOCUMENT_ROOT/config.json
fi


echo ""
echo "***********************************************************"
echo " Starting Streaming-Plus Services                          "
echo "***********************************************************"

## Start Supervisord
supervisord -c /etc/supervisor/supervisord.conf