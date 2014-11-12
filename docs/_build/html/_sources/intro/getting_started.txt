.. _getting-started:

===============
Getting started
===============

First of all, download *Renzo* latest version using Git

.. code-block:: bash

    cd your/webroot/folder;
    git clone xxxxxxxxxxxx ./;

Use `Composer <https://getcomposer.org/doc/00-intro.md#globally>`_ to download dependancies

.. code-block:: bash

    composer install;

Then copy `conf/config.default.json` file to `conf/config.json`.

Prepare your server
-------------------

You can either use *Apache* or *Nginx* with Renzo. An example virtual host is provided for each:

* apache.conf
* nginx.conf

When your virtual host is ready, just go to your website to begin with the setup assistant.