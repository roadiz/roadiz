# Roadiz CMS

[![Build Status](https://travis-ci.org/roadiz/roadiz.svg?branch=master)](https://travis-ci.org/roadiz/roadiz)
[![Coverage Status](https://coveralls.io/repos/roadiz/roadiz/badge.png?branch=master)](https://coveralls.io/r/roadiz/roadiz?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/roadiz/roadiz/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/roadiz/roadiz/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/b9240404-8621-4472-9a2d-634ad918660d/mini.png)](https://insight.sensiolabs.com/projects/b9240404-8621-4472-9a2d-634ad918660d)
[![Crowdin](https://d322cqt584bo4o.cloudfront.net/roadiz-cms/localized.png)](https://crowdin.com/project/roadiz-cms) 
![MIT license](http://img.shields.io/badge/license-MIT-brightgreen.svg)

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

The following instructions are a summary for our documentation [*Getting started* section](http://docs.roadiz.io/en/latest/developer/first-steps/getting_started.html).

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

#### Install from sources

This install process needs an *SSH* connexion to your server with *Git*
and [*Composer*](https://getcomposer.org/doc/00-intro.md#globally).
It will enable you to make Roadiz updates more easily than with the bundle version.
This is the **recommended** method.

1. Clone current repository to your web root: `git clone -b develop https://github.com/roadiz/roadiz.git ./`
2. Install dependencies with *Composer*: `composer install --no-dev -o`. At the end of the install, 
a custom script will copy for you a default *configuration* file and `dev` and `install` environment entry points.
3. Create an *Apache* or *Nginx* virtual host based on files in `samples/` folder.
**If you don’t have any permission to create a virtual host,
execute `bin/roadiz config --generate-htaccess` to create `.htaccess` files.**
4. If Roadiz is not setup on your own computer (*localhost*), add your IP address 
in the `dev.php` and `install.php` files to authorize your computer to access these two entry points.
5. Go to your web-browser using *install.php* after your server domain name to launch Install wizard.

Once you’ve installed *Roadiz*, just type `/rz-admin` after your server domain name, without *install.php*, to reach backoffice interface.

### Use our custom Vagrant box for development

Roadiz comes with a dedicated `Vagrantfile` which is configured to run a *LEMP* stack 
(nginx + PHP-FPM + MariaDB), an *Apache Solr server* and a *MailCatcher* service. 
This will be useful to develop your website on your local computer. Once you’ve cloned Roadiz’ sources
just do a `vagrant up` in Roadiz’ folder. Then Vagrant will run your code in `/var/www`
and you will be able to completely use `bin/roadiz` commands without bloating your
computer with lots of binaries.

Once vagrant box has provisioned you will be able to use:

* `http://localhost:8080/install.php` to proceed to install.
* `http://localhost:8983/solr` to use *Apache Solr* admin.
* `http://localhost:8080/phpmyadmin` for your *MySQL* db admin.
* `http://localhost:1080` for *MailCatcher*.

Be careful, **Windows users**, this `Vagrantfile` is configured to use a *NFS* fileshare.
Do not hesitate to disable it if you did not setup a NFS emulator. For OS X and Linux user
this is built-in your system, so have fun!

#### Provisioners

If you don’t need Apache Solr or any development tools on your Vagrant VM, you can
choose the `roadiz` provisioner which only set up the LEMP stack. So that you can
use *Composer* directly on your host machine to take benefit of your cache 
if you have lots of Roadiz websites.

```bash
# Just LEMP stack, no MailCatcher, no Solr, no Composer, no NPM, no grunt, no bower
vagrant up --no-provision
vagrant provision --provision-with roadiz

# If you need Solr
# do not use space after comma
vagrant up --no-provision
vagrant provision --provision-with roadiz,solr

# If you need dev tools
vagrant up --no-provision
vagrant provision --provision-with roadiz,devtools

# If you need MailCatcher
vagrant up --no-provision
vagrant provision --provision-with roadiz,mailcatcher
```

When you use default `vagrant up` command, it’s the same as using:

```bash
# Default vagrant up provisioners
vagrant up --no-provision
vagrant provision --provision-with roadiz,mailcatcher,solr,devtools
```

Pay attention that *mailcatcher* and *solr* provision scripts may take several 
minutes to run as they have to download many sources for their installation.

If you already provisioned your Vagrant and you just want to add *mailcatcher* for example,
you can type `vagrant provision --provision-with mailcatcher`. No data will
be lost in your Vagrant box.

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

### Custom mailer

Roadiz can use a *SMTP* mail server to send every message out of your website.
We strongly recommend you to configure with an **external** SMTP service such as *Mandrill app* 
so you don’t have to use your server built-in *sendmail* command.

```yml
mailer:
    type: smtp
    host: smtp.mandrillapp.com
    port: 587
    encryption: false
    username: my-mandrill-email
    password: my-mandrill-apikey
```

### Images processing

Roadiz use [*Image Intervention*](http://image.intervention.io/) library to automatically create a lower quality
version of your image if they are too big. You can define this threshold value
in the `assetsProcessing` section. `driver` and `defaultQuality` will be also 
use for the on-the-fly image processing with [*Intervention Request*](https://github.com/ambroisemaupate/intervention-request) library.

```yml
assetsProcessing:
    # gd or imagick (gd does not support TIFF and PSD formats)
    driver: gd
    defaultQuality: 90
    # pixel size limit () after roadiz
    # should create a smaller copy.
    maxPixelSize: 1280
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
                --ignore="*/node_modules/*,*/.AppleDouble,*/vendor/*,*/cache/*,*/gen-src/*,*/tests/*,*/bin/*" \
                -p ./
# … and correct
bin/phpcbf --report=full --report-file=./report.txt \
                --extensions=php --warning-severity=0 \
                --standard=PSR2 \
                --ignore="*/node_modules/*,*/.AppleDouble,*/vendor/*,*/cache/*,*/gen-src/*,*/tests/*,*/bin/*" \
                -p ./
```

### Migrating with an existing database

When you import your existing database, before performing any database migration,
you **must** regenerate first all node-sources PHP classes.

```bash
bin/roadiz core:sources -r
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
bin/roadiz cache -a --env=prod;
```

The `cache -a --env=prod` command force Doctrine to purge its metadata cache for the *production* environment.
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
bin/roadiz cache -a --env=prod;
```

### Troubleshooting

#### Empty caches manually for an environment

If you experience errors only on a dedicated environment such as `prod`, `dev` or `install`, it means that cache
is not fresh for these environments. As a first try, you can **delete** `cache/prod` folder and retry.
For your information, `preview` entry point use the `dev` environment, so if errors occur in preview: **delete**
`cache/dev` folder.

If you still get errors from a specific env and you are using an *OPcode* cache or *var cache* (APC, Xcache), try restarting your PHP daemon in
order to purge these memory caches.

#### Problem with entities and Doctrine cache?

After each Roadiz upgrade you should upgrade your node-sources entity classes and upgrade database schema.

```bash
bin/roadiz core:sources -r
bin/roadiz orm:schema-tool:update --force
bin/roadiz cache -a --env=prod;

```

If you are using a *OPCode var cache* like *APC*, *XCache*, you should purge it as Roadiz stores doctrine
configuration there for better performances.

### Licenses

* *Roadiz* is released under **MIT** licence
* *RZ Icons* font-icon is released under **MIT** licence too
* *Roadiz Sans* font family is released under **GPL+FE** licence and is edited by *Nonpareille* type foundry
