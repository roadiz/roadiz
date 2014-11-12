.. _using-twig:

==========
Using Twig
==========

.. Note::

    Twig is the default rendering engine for *Renzo* CMS. You’ll find its documentation at http://twig.sensiolabs.org/doc/templates.html

When you use Dynamic routing within your theme, Renzo will automatically assign some variables for you:

- request: Main request object
- head
    - ajax: `boolean`
    - cmsVersion
    - cmsBuild
    - devMode: `boolean`
    - baseUrl
    - filesUrl
    - resourcesUrl
    - ajaxToken
    - fontToken
- session
    - messages
    - id
    - user
- securityContext

There are some more content only available from *FrontendControllers*:

* **_default_locale**
* meta
    * siteName
    * siteCopyright
    * siteDescription

Then, in each dynamic routing *actions* you will need this line ``$this->storeNodeAndTranslation($node, $translation);``
in order to make page content available from your Twig template:

* node
* nodeSource
* translation
* pageMeta
    * title
    * description
    * keywords

All these data will be available in your Twig template using ``{{ }}`` syntax.
For example use ``{{ pageMeta.title }}`` inside your head’s ``<title>`` tag.
You can of course call objects members within Twig using the *dot* separator.

.. code:: html+jinja

    <article>
        <h1>{{ nodeSource.title }}</h1>
        <div>{{ nodeSource.content|markdown }}</div>

        {% set images = nodeSource.handler.getDocumentsFromFieldName('images') %}
        {% for image in images %}

            {% set imageMetas = image.documentTranslations.first %}
            <figure>
                {{ image.viewer.getDocumentByArray({ width:200 })|raw }}
                <figcaption>{{ imageMetas.name }} — {{ imageMetas.copyright }}</figcaption>
            </figure>
        {% endfor %}
    </article>