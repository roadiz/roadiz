# RZCMS v3
## REZO ZERO CMS

### Installation

* Clone current repository to your web root
* Edit your own config file: `cp ./conf/config.default.json ./conf/config.json`
* Add your database configuration
* Install dependencies: `composer install` or `composer update`
* Install database: `php index.php install` or go to your web-browser to launch Install wizard.

### Run self tests

* *PHPUnit tests*: `phpunit --bootstrap bootstrap.php ./Tests`
* *Code quality*, use PHP_CodeSniffer with *Symfony2 standard*: `phpcs --report=full --report-file=./report.txt --extensions=php --ignore=*/node_modules/*,*/.AppleDouble,*/vendor/*,*/cache/*,*/sources/*,*/Tests/* -p ./`, follow instructions at https://github.com/opensky/Symfony2-coding-standard
