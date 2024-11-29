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
mkdir -p $SP_DATA_PATH/app
mkdir -p $SP_DATA_PATH/jellyfin
mkdir -p $SP_DATA_PATH/jellyfin/cache
mkdir -p $SP_DATA_PATH/media
mkdir -p $SP_DATA_PATH/env

#Cambio i loghi di Jellyfin
cp -r /var/src/img/ /usr/share/jellyfin/web/assets/

#Installa StreamingCommunity
python3 -m venv /data/env && /data/env/bin/pip install StreamingCommunity

chown -R $USER_NAME:$GROUP_NAME $SP_DATA_PATH/

## Start Supervisord
supervisord -c /etc/supervisor/supervisord.conf