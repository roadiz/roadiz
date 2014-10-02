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
* *Code quality*, use PHP_CodeSniffer with *PSR2 standard*:
```
phpcs --report=full --report-file=./report.txt --extensions=php --warning-severity=0 --standard=PSR2 --ignore=*/node_modules/*,*/.AppleDouble,*/vendor/*,*/cache/*,*/sources/*,*/Tests/* -p ./
```

### Migrating with an existing database

When you import your existing database, you must regenerate all node-types sources classes.

```
bin/renzo core:node:types --regenerateAllEntities
```

This will parse every node-types from your database and recreate PHP classes in your `sources/GeneratedNodeSources` folder.

### Upgrading database schema

If you just updated your *RZCMS v3* sources files, you shoud perform a database migration.
First **be sure your node-types sources classes exist**.
If you did’nt generate them just have a look at *Migrating with an existing database* section.
Then you can perform migration :

```
bin/renzo schema --update
```

Be careful, check the output to see if any node-source data will be deleted!
Doctrine will parse every node-type classes to see new and deprecated node-types.
Then when you are sure to perform migration, just do:

```
bin/renzo schema --update --execute
```

