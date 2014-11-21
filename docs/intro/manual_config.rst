.. _manual_config:

Manual configuration
====================

This section explains how main configuration file works as you would find
it more convinient than launching Install theme for each update.

Your ``config.json`` file is built in parts. Each one match a *service* of your CMS.

The most important part is the database credentials part:

.. code-block:: json

    "doctrine": {
        "driver": "pdo_mysql",
        "host": "localhost",
        "user": "",
        "password": "",
        "dbname": ""
    }

Roadiz uses *Doctrine ORM* to store your data. It will directly pass this part to *Doctrine* so
you can use every available drivers or options from its documentation at
http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html


Solr endpoint
-------------

Roadiz can use an *Apache Solr* search-engine to index nodes-sources.
Add this to your `config.json` to link your CMS to your *Solr* server:

.. code-block:: json

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


Entities paths
--------------

Roadiz uses *Doctrine* to map object entities to database tables.
In order to make Roadiz more extensible, you can add your own paths to the ``entities`` part.

.. code-block:: json

    "entities": [
        "src/Roadiz/Core/Entities",
        "src/Roadiz/Core/AbstractEntities",
        "gen-src/GeneratedNodeSources"
    ]