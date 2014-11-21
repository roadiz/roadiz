.. _upgrading:

=========
Upgrading
=========

**Always do a database backup before upgrading.**

Download latest version using *Git*

.. code-block:: bash

    cd your/webroot/folder;
    git pull origin master;

Use *Composer* to update dependancies

.. code-block:: bash

    composer update;

Then run database schema update

.. code-block:: bash

    bin/roadiz schema --update;

If migration summary is OK, perform the changes

.. code-block:: bash

    bin/roadiz schema --update --execute;
    bin/roadiz cache --clear-all

Upgrading Node-types source entities
------------------------------------

If some Doctrine errors occur about some fields missing in your *NodesSources*,
you must *regenerate all entities* source files

.. code-block:: bash

    bin/roadiz core:node:types --regenerateAllEntities;
    bin/roadiz schema --update;

Verify here that no data field will be removed and apply changes

.. code-block:: bash

    bin/roadiz schema --update --execute;
    bin/roadiz cache --clear-all
