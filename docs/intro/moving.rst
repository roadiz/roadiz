.. _moving:

Moving a website to another server
==================================

Before moving your website, make sure you have backed up your data:

* Dump your database, using `mysql_dump` or `pg_dump` tools.
* Archive your `/files` folder, it will contain all your documents and font files

From this point you can install your new webserver, as described in :ref:`Install section <getting-started>`.

Then import your dump and files into your new server.

Once you’ve imported your database, you must edit manually your `conf/config.json`, you can reuse the former server’s one and adapt its database credentials.

.. warning::
    **Do not perform any schema update if no gen-src\\GeneratedNodeSources classes is available**, it will erase your NodesSources data as their entities files haven’t been generated yet.

When you’ve edited your `conf/config.json` file, regenerate your entities source files

.. code-block:: bash

    bin/roadiz core:node:types --regenerateAllEntities;

Now you can perform a schema update without losing your nodes data

.. code-block:: bash

    bin/roadiz schema --update;
    bin/roadiz schema --update --execute;

    bin/roadiz cache --clear-all