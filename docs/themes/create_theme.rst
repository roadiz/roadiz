.. _create-theme:

================
Creating a theme
================


First copy ``DefaultTheme`` folder and rename it against your new theme.
Do not forget to rename every references in:

* **Folder name** and **Class namespace** must be the same (Ex: “MyAwesomeTheme”) for making autoloader works with your theme.
* **Theme entry point class**: your main theme class must be named after your folder name plus ``App`` suffix (Ex: “MyAwesomeThemeApp.php”)
* **routes.yml**: rename every route class path using your namespace:

.. code-block:: yaml

    # This route is required!
    homePage:
        path:     /
        defaults: { _controller: Themes\MyAwesomeTheme\MyAwesomeThemeApp::homeAction }
    homePageLocale:
        path:     /{_locale}
        defaults: { _controller: Themes\MyAwesomeTheme\MyAwesomeThemeApp::homeAction }
        requirements:
            # Use every 2 letter codes
            _locale: "[a-z]{2}"

    contactPage:
        path:     /contact
        defaults: { _controller: Themes\MyAwesomeTheme\Controllers\ContactController::indexAction }
    contactPageLocale:
        path:     /{_locale}/contact
        defaults: { _controller: Themes\MyAwesomeTheme\Controllers\ContactController::indexAction }
        requirements:
            # Use every 2 letter codes
            _locale: "[a-z]{2}"

    feedRSS:
        path:     /feed
        defaults: { _controller: Themes\MyAwesomeTheme\Controllers\FeedController::indexAction }
    sitemap:
        path:     /sitemap.xml
        defaults: { _controller: Themes\MyAwesomeTheme\Controllers\SitemapController::indexAction }
    defaultRemoveTrailingSlash:
        path: /{url}
        defaults: { _controller: Themes\MyAwesomeTheme\MyAwesomeThemeApp::removeTrailingSlashAction }
        requirements:
            url: .*/$
        methods: [GET]


* Create your own ``config.json`` file:

.. code-block:: json

    {
        "name": "My awesome theme",
        "author": "Ambroise Maupate",
        "copyright": "REZO ZERO",
        "themeDir": "MyAwesomeTheme",
        "supportedLocale": ["en"],
        "versionRequire": "1.0.0",
        "importFiles": {
            "roles": [],
            "groups": [],
            "settings": [],
            "nodetypes": [],
            "tags": [],
            "nodes": []
        }
    }

* Edit your main class informations (``MyAwesomeThemeApp.php``)

.. code-block:: php
   :linenos:
   :emphasize-lines: 11,25,30,34,38,42

    /*
     * Copyright REZO ZERO 2014
     *
     * Description
     *
     * @file MyAwesomeThemeApp.php
     * @copyright REZO ZERO 2014
     * @author Ambroise Maupate
     */

    namespace Themes\MyAwesomeTheme;

    use RZ\Roadiz\CMS\Controllers\FrontendController;
    use RZ\Roadiz\Core\Kernel;
    use RZ\Roadiz\Core\Entities\Node;
    use RZ\Roadiz\Core\Entities\Translation;
    use RZ\Roadiz\Core\Utils\StringHandler;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Exception\ResourceNotFoundException;
    /**
     * MyAwesomeThemeApp class
     */
    class MyAwesomeThemeApp extends FrontendController
    {
        /**
         * {@inheritdoc}
         */
        protected static $themeName =      'My awesome theme';
        /**
         * {@inheritdoc}
         */
        protected static $themeAuthor =    'Ambroise Maupate';
        /**
         * {@inheritdoc}
         */
        protected static $themeCopyright = 'REZO ZERO';
        /**
         * {@inheritdoc}
         */
        protected static $themeDir =       'MyAwesomeTheme';
        /**
         * {@inheritdoc}
         */
        protected static $backendTheme =    false;

        …
    }


Static routing
--------------

Before searching for a node’s Url (Dynamic routing), Roadiz will parse your theme ``route.yml``
to find static controllers and actions to execute.
Static actions just have to comply with the ``Request`` / ``Response`` scheme.
It is adviced to add ``$_locale`` and ``$_route`` optional arguments to better handle
multilingual pages.

.. code-block:: yaml

    foo:
        path:     /foo
        defaults: { _controller: Themes\MyAwesomeTheme\Controllers\FooBarController::fooAction }
    bar:
        path:     /{_locale}/bar
        defaults: { _controller: Themes\MyAwesomeTheme\Controllers\FooBarController::barAction }
        requirements:
            # Use every 2 letter codes
            _locale: "[a-z]{2}"


.. code-block:: php

    public function fooAction(Request $request) {

        $translation = $this->bindLocaleFromRoute($request, 'en');
        $this->prepareThemeAssignation(null, $translation);

        return new Response(
            $this->getTwig()->render('foo.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    public function barAction(
        Request $request,
        $_locale = null,
        $_route = null
    ) {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);


        return new Response(
            $this->getTwig()->render('bar.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

.. _dynamic-routing:

Dynamic routing
---------------

.. Note::

    Every node-types will be handled by a specific ``Controller``.
    If your created a “Page” type, Roadiz will search for a ``…\\Controllers\\PageController`` class and
    it will try to execute the ``indexAction`` method.

An indexAction method must comply with the following signature.
It will take the HttpFoundation’s Request as first then a ``Node`` and a ``Translation`` instances.
These two last arguments will be useful to generate your page information and to
render your current node.

.. code-block:: php

    /**
     * Default action for any Page node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param RZ\Roadiz\Core\Entities\Node              $node
     * @param RZ\Roadiz\Core\Entities\Translation       $translation
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null
    ) {
        $this->prepareThemeAssignation($node, $translation);

        $this->getService('stopwatch')->start('twigRender');

        return new Response(
            $this->getTwig()->render('types/page.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

As *Symfony* controllers do, every Roadiz controllers actions have to return a valid ``Response`` object.
