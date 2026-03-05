#!/bin/sh

# Jalankan queue worker di background
php artisan queue:work --sleep=3 --tries=3 --timeout=60 &

# Jalankan FrankenPHP — cari Caddyfile di lokasi yang tersedia
if [ -f /Caddyfile ]; then
    frankenphp run --config /Caddyfile
elif [ -f /etc/caddy/Caddyfile ]; then
    frankenphp run --config /etc/caddy/Caddyfile
else
    frankenphp run
fi