# RZCMS v3
## REZO ZERO CMS

### Installation

* Clone current repository to your web root
* Create an **empty** database for your new website.
**Don’t setup your website on an already used database, it will erase any existing table on it.**
* Install dependencies: `composer install`, if you don’t have *Composer* installed on your machine
follow official doc at https://getcomposer.org/doc/00-intro.md#globally
* Generate an optimized autoloader: `composer dumpautoload -o`
* Go to your web-browser to launch Install wizard.

#### Database connexion

To connect manually to your database, you can add this to your `config.json`:

```
"doctrine": {
    "driver": "pdo_mysql",
    "host": "localhost",
    "user": "",
    "password": "",
    "dbname": ""
}
```

If you prefer socket:

```
"doctrine": {
    "driver": "pdo_mysql",
    "unix_socket": "",
    "user": "",
    "password": "",
    "dbname": ""
}
```

For more options you can visit *Doctrine* website: http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html

### Apache Solr

RZCMS v3 can use Apache Solr search-engine to index nodes.
Add this to your `config.json` to link your RZCMS install to your Solr server:

```
"solr": {
    "endpoint": {
        "localhost": {
            "host":"localhost",
            "port":"8983",
            "path":"/solr",
            "core":"mycore",
            "timeout":3,
            "username":"",
            "password":""
        }
    }
}
```

### Run self tests

* *PHPUnit tests*: `phpunit --bootstrap bootstrap.php ./tests`
* *Code quality*, use PHP_CodeSniffer with [*Symfony2 standard*](https://github.com/lapistano/Symfony2-coding-standard):
```
phpcs --report=full --report-file=./report.txt --extensions=php --warning-severity=0 --standard=PSR2 --ignore=*/node_modules/*,*/.AppleDouble,*/vendor/*,*/cache/*,*/sources/*,*/Tests/* -p ./
```
* Follow instructions at https://github.com/opensky/Symfony2-coding-standard
