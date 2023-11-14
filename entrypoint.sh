#!/bin/bash
printenv | grep -v "no_proxy" >> /etc/environment
#cron
composer install
bin/console d:m:m
php-fpm
