web: php -S 0.0.0.0:$PORT -t public
worker: php artisan queue:work --sleep=3 --tries=3 --max-time=3600