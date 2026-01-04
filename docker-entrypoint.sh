#!/bin/sh

# Start SSH Proxy in background
node ssh-proxy-server.cjs &

# Start Laravel server
php artisan serve --host=0.0.0.0 --port=8000
