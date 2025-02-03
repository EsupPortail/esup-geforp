#!/bin/bash
php bin/console cache:clear --env=prod 
chown -R www-data var/cache/
