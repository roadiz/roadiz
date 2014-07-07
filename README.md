# RZCMS v3
## REZO ZERO CMS

### Installation

* Clone current repository to your web root
* Edit your own config file: `cp ./conf/config.default.json ./conf/config.json`
* Add your database configuration
* Install dependencies: `composer install` or `composer update`
* Install database: `php index.php install`

### Run self tests

* `phpunit --bootstrap bootstrap.php ./Tests`

