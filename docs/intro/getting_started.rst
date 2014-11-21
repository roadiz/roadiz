.. _getting-started:

===============
Getting started
===============

CMS Structure
-------------

* ``bin`` : Contains the Roadiz CLI executable
* ``cache`` : Every caches files for *Twig* templates and *SLIR* images
* ``conf`` : Your setup configuration file(s)
* ``docs`` : Current documentation working files
* ``files`` : Documents and fonts files root
* ``gen-src`` : Generated PHP code for Doctrine and your Node-types entities
* ``src`` : Roadiz CMS logic and core source code
* ``tests`` : PHP Unit tests root
* ``themes`` : Contains your themes and systems themes such as *Rozier* and *Install*
* ``vendor`` : Dependencies folder managed by *Composer*

Requirements
------------

* Nginx or Apache
* PHP 5.4+
* ``php5-gd`` extension
* ``php5-intl`` extension
* ``php5-imap`` extension
* ``php5-curl`` extension
* Zip/Unzip
* cUrl
* PHP cache (APC/XCache) + Var cache
* Composer

Prepare your server
-------------------

You can either use *Apache* or *Nginx* with Roadiz. An example virtual host is provided for each:

* ``apache.conf``
* ``nginx.conf``

Installation
------------

First of all, download *Roadiz* latest version using Git

.. code-block:: bash

    cd your/webroot/folder;
    git clone xxxxxxxxxxxx ./;

Use `Composer <https://getcomposer.org/doc/00-intro.md#globally>`_ to download dependancies

.. code-block:: bash

    composer install;

.. note::
    Once your website will be ready and every node-types created you will be able to
    optimize *Composer* autoload process: ``composer dumpautoload -o``

Then copy `conf/config.default.json` file to `conf/config.json`.

.. code-block:: bash

    cp conf/config.default.json conf/config.json;

When your virtual host is ready, just go to your website to begin with the setup assistant.