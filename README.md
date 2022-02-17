Geforp2
======

Geforp2 : nouvelle version sous Symfony 5.4

Configuration requise
------------

### PHP

* version 7.4 minimum 
* extensions :
    * json
    * ctype
* modules :
    * pdo_mysql
    * openssl
    * apc
    * mbstring
    * curl
    * fileinfo

### Symfony5.4

### MySQL


### ElasticSearch

### Unoconv

### Shibboleth

Installation
------------

### Prérequis

- Composer installé : http://www.coolcoyote.net/php-mysql/installation-de-composer-sous-linux-et-windows

-----------------
Installation
-----------------

### Projet

* git clone https://weblab.univ-amu.fr/polen/geforp2.git
* composer install

### Commandes base de données
* php bin/console doctrine:database:create
* php bin/console make:migration
* php bin/console doctrine:migrations:migrate
* php bin/console doctrine:fixtures:load
