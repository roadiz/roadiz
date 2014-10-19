===============
Getting started
===============

Prepare your files
------------------

First of all, download RZCMS latest version using Git::

    cd your/webroot/folder;
    git clone xxxxxxxxxxxx ./;

Use *Composer* to download dependancies::

    composer install;

Then copy `conf/config.default.json` file to `conf/config.json`.

Prepare your server
-------------------

You can either use *Apache* or *Nginx* with Renzo. An example virtual host is provided for each:

* apache.conf
* nginx.conf

When your virtual host is ready, just go to your website to begin with the setup assistant.