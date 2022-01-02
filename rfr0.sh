#!/bin/sh
php artisan down
git pull origin master
composer install 

#php artisan migrate:fresh --seed
php artisan migrate 
php artisan db:seed --class="sysDataSeed"

php artisan cache:clear
php artisan queue:restart
php artisan up
