.. _using-twig:

==========
Using Twig
==========

.. Note::

    Twig is the default rendering engine for *Roadiz* CMS. You’ll find its documentation at http://twig.sensiolabs.org/doc/templates.html

When you use :ref:`Dynamic routing <dynamic-routing>` within your theme, Roadiz will automatically assign some variables for you::

    * request
    * head
        * ajax
        * cmsVersion
        * cmsBuild
        * devMode
        * baseUrl
        * filesUrl
        * resourcesUrl
        * ajaxToken
        * fontToken
    * session
        * messages
        * id
        * user
    * securityContext

There are some more content only available from *FrontendControllers*::

    * _default_locale
    * meta
        * siteName
        * siteCopyright
        * siteDescription

Then, in each dynamic routing *actions* you will need this line ``$this->storeNodeAndTranslation($node, $translation);``
in order to make page content available from your Twig template::

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

.. code-block:: html+jinja

    <article>
        <h1><a href="{{ nodeSource.handler.getUrl }}">{{ nodeSource.title }}</a></h1>
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

Additional filters
------------------

Roadiz’s Twig environment implements some useful filters, such as:

* ``markdown``: Convert a markdown text to HTML
* ``inlineMarkdown``: Convert a markdown text to HTML without parsing *block* elements (useful for just italics and bolds)
* ``centralTruncate(length, offset, ellipsis)``: Generate an ellipsis at the middle of your text (useful for filenames). You can decenter the ellipsis position using ``offset`` parameter, and even change your ellipsis character with ``ellipsis`` parameter.

Standard filters and extensions are also available:

* ``{{ path('myRoute') }}``: for generating static routes Url.
* ``truncate`` and ``wordwrap`` which are parts of the `Text Extension <http://twig.sensiolabs.org/doc/extensions/text.html>`_ .
