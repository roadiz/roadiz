# Roadiz CMS

[![Build Status](https://travis-ci.org/roadiz/roadiz.svg?branch=develop)](https://travis-ci.org/roadiz/roadiz)
[![Coverage Status](https://coveralls.io/repos/roadiz/roadiz/badge.png?branch=develop)](https://coveralls.io/r/roadiz/roadiz?branch=develop)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/roadiz/roadiz/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/roadiz/roadiz/?branch=develop)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/b9240404-8621-4472-9a2d-634ad918660d/mini.png)](https://insight.sensiolabs.com/projects/b9240404-8621-4472-9a2d-634ad918660d)

Roadiz is a polymorphic CMS based on a node system which can handle many type of services.
It’s based on Symfony components and Doctrine ORM for maximum performances and security.

* *Roadiz* is released under **MIT** licence
* *RZ Icons* font-icon is released under **MIT** licence too
* *Roadiz Sans* font family is released under **GPL+FE** licence and is edited by *Nonpareille* type foundry

### Documentation

* *Roadiz* website: http://www.roadiz.io
* *Read the Docs* complete documentation can be found at http://docs.roadiz.io
* *API* documentation can be found at http://api.roadiz.io

### Installation

* Clone current repository to your web root
* Create an **empty** database for your new website.
**Don’t setup your website on an already used database, it will erase any existing table on it.**
* Install dependencies: `composer install`, if you don’t have *Composer* installed on your machine
follow official doc at https://getcomposer.org/doc/00-intro.md#globally
* Generate an optimized autoloader: `composer dumpautoload -o`
* Create an *Apache* or *Nginx* virtual host based on samples in `samples/` folder.
**If you don’t have any permission to create a virtual host,
execute `bin/roadiz config --generateHtaccess` to create `.htaccess` files to protect critical informations
from bad requests.**
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

You can specify a table prefix adding `"prefix":"myprefix"` if you can’t create a dedicated database for your project
and you need to use Roadiz side by side with other tables.
But we strongly recommend you to respect the 1 app = 1 database motto.

For more options you can visit *Doctrine* website: http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html

### Apache Solr

Roadiz can use Apache Solr search-engine to index nodes.
Add this to your `config.json` to link your Roadiz install to your Solr server:

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

* Install *dev* dependencies: `composer update --dev`
* *PHPUnit tests*:
```
./vendor/bin/phpunit -v --bootstrap=bootstrap.php tests/
```
* *Code quality*, use PHP_CodeSniffer with *PSR2 standard*:

```
./vendor/bin/phpcs --report=full --report-file=./report.txt \
                --extensions=php --warning-severity=0 \
                --standard=PSR2 \
                --ignore=*/node_modules/*,*/.AppleDouble,*/vendor/*,*/cache/*,*/gen-src/*,*/Tests/* \
                -p ./
```

### Migrating with an existing database

When you import your existing database, you must regenerate all node-types sources classes.

```
bin/roadiz core:node-types --regenerateAllEntities
```

This will parse every node-types from your database and recreate PHP classes in your `gen-src/GeneratedNodeSources` folder.

### Upgrading database schema

If you just updated your *Roadiz* sources files, you shoud perform a database migration.
First **be sure your node-types sources classes exist**.
If you did not generate them just have a look at *Migrating with an existing database* section.
Then you can perform migration :

```
bin/roadiz orm:schema-tool:update --dump-sql
```

Be careful, check the output to see if any node-source data will be deleted!
Doctrine will parse every node-type classes to see new and deprecated node-types.
Then when you are sure to perform migration, just do:

```
bin/roadiz orm:schema-tool:update --force
bin/roadiz cache --clear-all;
```

The `cache --clear-all` command force Doctrine to purge its metadata cache.
**Be careful, this won’t purge APC or XCache. You will need to do it manually.**

### Managing your own database entities

You can create a theme with your own entities. Just add your `Entities` folder
to the global configuration file.

```
"entities": [
    "src/Roadiz/Core/Entities",
    "src/Roadiz/Core/AbstractEntities",
    "gen-src/GeneratedNodeSources",
    "add/here/your/entities/folder",
    …
]
```

Verify if everything is OK by checking migrations:

```
bin/roadiz orm:schema-tool:update --dump-sql;
```

If you see your entities being created and no system database erased, just `--execute` your migration.
If Doctrine send some error, you probably need to clear metadata cache:

```
bin/roadiz cache --clear-all;
```

### Problem with entities and Doctrine cache?

After each Roadiz upgrade you should upgrade your node-sources entity classes and upgrade database schema.

```
bin/roadiz core:node-types --regenerateAllEntities;
bin/roadiz orm:schema-tool:update --force
bin/roadiz cache --clear-all;

```

If you are using a *OPCode var cache* like *APC*, *XCache*, you should purge it as Roadiz stores doctrine
configuration there for better performances.
