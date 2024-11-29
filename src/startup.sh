#!/bin/bash

python3 -m venv /data/env
/data/env/bin/pip install --upgrade StreamingCommunity

#cd /var/www
#php artisan migrate:fresh --seed
#php artisan serve --host=0.0.0.0