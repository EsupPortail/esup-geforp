#!/bin/bash
cd /var/www/geforp2
php bin/console doctrine:database:create
#mysql geforp3 <sygefor3.sql
mysql geforp2 <geforp2-28022023.sql
mysql geforp2 <init-allshib.sql

