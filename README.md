# RZCMS v3
## REZO ZERO CMS

### Installation

* Clone current repository to your web root
* Edit your own config file: `cp ./conf/config.default.json ./conf/config.json`
* Add your database configuration
* Install dependencies: `composer install` or `composer update`
* Install database: `php index.php install` or go to your web-browser to launch Install wizard.

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
* *Code quality*, use PHP_CodeSniffer with *Symfony2 standard*:
`phpcs --report=full --report-file=./report.txt --extensions=php --ignore=*/node_modules/*,*/.AppleDouble,*/vendor/*,*/cache/*,*/sources/*,*/Tests/* -p ./`
Follow instructions at https://github.com/opensky/Symfony2-coding-standard
