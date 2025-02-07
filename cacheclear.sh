#!/bin/bash
php bin/console cache:clear --env=dev 
chown -R www-data var/cache/
