# Roadiz CMS

[![Build Status](https://travis-ci.org/roadiz/roadiz.svg?branch=develop)](https://travis-ci.org/roadiz/roadiz)
[![Coverage Status](https://coveralls.io/repos/roadiz/roadiz/badge.png?branch=develop)](https://coveralls.io/r/roadiz/roadiz?branch=develop)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/roadiz/roadiz/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/roadiz/roadiz/?branch=develop)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/b9240404-8621-4472-9a2d-634ad918660d/mini.png)](https://insight.sensiolabs.com/projects/b9240404-8621-4472-9a2d-634ad918660d)
[![Crowdin](https://d322cqt584bo4o.cloudfront.net/roadiz-cms/localized.png)](https://crowdin.com/project/roadiz-cms)

[![Join the chat at https://gitter.im/roadiz/roadiz](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/roadiz/roadiz?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Roadiz is a modern CMS based on a polymorphic node system which can handle many types of services and contents.
Its back-office has been developed with a high sense of design and user experience.
Its theming system is built to live independently from back-office allowing easy switching
and multiple themes for one content basis. For example, it allows you to create one theme
for your desktop website and another one for your mobile, using the same node hierarchy.
Roadiz is released under MIT license, so you can reuse
and distribute its code for personal and commercial projects.

### Documentation

* *Roadiz* website: http://www.roadiz.io
* *Read the Docs* documentation can be found at http://docs.roadiz.io
* *API* documentation can be found at http://api.roadiz.io

### Installation

The following instructions are a summary for our documentation [*Getting started* section](http://docs.roadiz.io/en/latest/intro/getting_started.html).

#### Requirements

Roadiz uses advanced PHP features and needs to run on a recent version
of PHP with a op-code cache.

* *Nginx* or *Apache* server
* **PHP 5.4.3+**
* ``php5-gd`` extension
* ``php5-intl`` extension
* ``php5-curl`` extension
* PHP cache (*APC/XCache*) + Var cache (strongly recommended) or *Memcached*
* Be sure that PHP has a read/write access to:
    * `/cache` folder
    * `/conf` folder
    * `/files` folder

##### For Apache users

* Requires `mod_rewrite` extension. Once Roadiz files and vendor are ready you’ll be able to
generate `.htaccess` files for you Apache server configuration:

```shell
bin/roadiz config --generate-htaccess
```

But it’s better if you can directly define rules in your virtual host configuration.
Use one of our templates in `/samples` folder.

* Requires `mod_expires` for managing your assets caching lifetime.

#### Install from bundle

Here is a simple install process if you already have a ready webserver.
For the moment no automatic update tool is available, if you want to regularly update
Roadiz against *Github*, prefer *install from sources*.

* Download Roadiz ZIP bundle from our [website](http://www.roadiz.io)
or from [github releases](https://github.com/roadiz/roadiz/releases) section.
* Unzip it into your server root folder (eg. `www/`)
* Go to your web-browser to launch Install wizard.

#### Install from sources

This process needs an *SSH* connexion to your server with *Git*
and [*Composer*](https://getcomposer.org/doc/00-intro.md#globally).
It will enable you to make Roadiz updates more easily than with the bundle version.
This is the **recommended** method if you are expert.

* Clone current repository to your web root
* Install dependencies with *Composer*: `composer install --no-dev`
* Copy `conf/config.default.yml` to `conf/config.yml`. After this command, `bin/roadiz` executable is available.
* Create an *Apache* or *Nginx* virtual host based on files in `samples/` folder.
**If you don’t have any permission to create a virtual host,
execute `bin/roadiz config --generate-htaccess` to create `.htaccess` files.**
* Go to your web-browser to launch Install wizard.

Once you’ve installed *Roadiz*, just type `/rz-admin` after your server domain name to reach backoffice interface.

### Database connexion

To connect manually to your database, you can add this to your `config.yml`:

```yml
doctrine:
    driver: pdo_mysql
    host: localhost
    user: null
    password: null
    dbname: null
    port: null
```

If you prefer socket:

```yml
doctrine:
    driver: pdo_mysql
    unix_socket: "/path/to/mysql.socket"
    user: null
    password: null
    dbname: null
```

You can specify a table prefix adding `prefix:"myprefix"` if you can’t create a dedicated database for your project
and you need to use Roadiz side by side with other tables. But we strongly recommend you to respect the 1 app = 1 database motto.

For more options you can visit *Doctrine* website: http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html

### Apache Solr

Roadiz can use Apache Solr search-engine to index nodes.
Add this to your `config.yml` to link your Roadiz install to your Solr server:

```yml
solr:
    endpoint:
        localhost:
            host: "localhost"
            port: "8983"
            path: "/solr"
            core: "mycore"
            timeout: 3
            username: ""
            password: ""
```

### Run self tests

* Install *dev* dependencies: `composer update --dev`
* *PHPUnit tests*:
```bash
bin/phpunit -v --bootstrap=tests/bootstrap.php tests/
```
* *Code quality*, use PHP_CodeSniffer with *PSR2 standard*:

```bash
# Check
bin/phpcs --report=full --report-file=./report.txt \
                --extensions=php --warning-severity=0 \
                --standard=PSR2 \
                --ignore=*/node_modules/*,*/.AppleDouble,*/vendor/*,*/cache/*,*/gen-src/*,*/tests/*,*/bin/* \
                -p ./
# … and correct
bin/phpcbf --report=full --report-file=./report.txt \
                --extensions=php --warning-severity=0 \
                --standard=PSR2 \
                --ignore=*/node_modules/*,*/.AppleDouble,*/vendor/*,*/cache/*,*/gen-src/*,*/tests/*,*/bin/* \
                -p ./
```

### Migrating with an existing database

When you import your existing database, before performing any database migration,
you **must** regenerate first all node-sources PHP classes.

```bash
bin/roadiz core:sources --regenerate
```

This will parse every node-types from your existing database and recreate PHP classes in `gen-src/GeneratedNodeSources` folder.

### Upgrading database schema

If you just updated your *Roadiz* sources files, you shoud perform a database migration.
First **be sure your node-types sources classes exist**.
If you did not generate them just have a look at *Migrating with an existing database* section.
Then you can perform migration :

```bash
bin/roadiz orm:schema-tool:update --dump-sql
```

Be careful, check the output to see if any node-source data will be deleted!
Doctrine will parse every node-type classes to see new and deprecated node-types.
Then when you are sure to perform migration, just do:

```bash
bin/roadiz orm:schema-tool:update --force
bin/roadiz cache --clear-all;
```

The `cache --clear-all` command force Doctrine to purge its metadata cache.
**Be careful, this won’t purge APC or XCache. You will need to do it manually.**

### Managing your own database entities

You can create a theme with your own entities. Just add your `Entities` folder
to the global configuration file.

```yml
entities:
    - src/Roadiz/Core/Entities
    - src/Roadiz/Core/AbstractEntities
    - gen-src/GeneratedNodeSources
    - add/here/your/entities/folder
```

Verify if everything is OK by checking migrations:

```bash
bin/roadiz orm:schema-tool:update --dump-sql;
```

If you see your entities being created and no system database erased, just `--execute` your migration.
If Doctrine send some error, you probably need to clear metadata cache:

```bash
bin/roadiz cache --clear-all;
```

### Problem with entities and Doctrine cache?

After each Roadiz upgrade you should upgrade your node-sources entity classes and upgrade database schema.

```bash
bin/roadiz core:sources --regenerate
bin/roadiz orm:schema-tool:update --force
bin/roadiz cache --clear-all;

```

If you are using a *OPCode var cache* like *APC*, *XCache*, you should purge it as Roadiz stores doctrine
configuration there for better performances.

### Licenses

* *Roadiz* is released under **MIT** licence
* *RZ Icons* font-icon is released under **MIT** licence too
* *Roadiz Sans* font family is released under **GPL+FE** licence and is edited by *Nonpareille* type foundry
