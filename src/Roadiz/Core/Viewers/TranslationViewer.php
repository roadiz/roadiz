<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Viewers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Core\Routing\RouteHandler;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class TranslationViewer
{
    /**
     * @var ParameterBag
     */
    private $settingsBag;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var Translation
     */
    private $translation;
    /**
     * @var PreviewResolverInterface
     */
    private $previewResolver;

    /**
     * @param EntityManager $entityManager
     * @param ParameterBag $settingsBag
     * @param RouterInterface $router
     * @param PreviewResolverInterface $previewResolver
     */
    public function __construct(
        EntityManager $entityManager,
        ParameterBag $settingsBag,
        RouterInterface $router,
        PreviewResolverInterface $previewResolver
    ) {
        $this->settingsBag = $settingsBag;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->previewResolver = $previewResolver;
    }

    /**
     * @return TranslationRepository
     */
    public function getRepository()
    {
        return $this->entityManager->getRepository(Translation::class);
    }

    /**
     * Return available page translation information.
     *
     * Be careful, for static routes Roadiz will generate a localized
     * route identifier suffixed with "Locale" text. In case of "force_locale"
     * setting to true, Roadiz will always use suffixed route.
     *
     * ## example return value
     *
     *     array (size=3)
     *       'en' =>
     *         array (size=4)
     *             'name' => string 'newsPage'
     *             'url' => string 'http://localhost/news/test'
     *             'locale' => string 'en'
     *             'active' => boolean false
     *             'translation' => string 'English'
     *       'fr' =>
     *         array (size=4)
     *             'name' => string 'newsPageLocale'
     *             'url' => string 'http://localhost/fr/news/test'
     *             'locale' => string 'fr'
     *             'active' => boolean true
     *             'translation' => string 'French'
     *       'es' =>
     *         array (size=4)
     *             'name' => string 'newsPageLocale'
     *             'url' => string 'http://localhost/es/news/test'
     *             'locale' => string 'es'
     *             'active' => boolean false
     *             'translation' => string 'Spanish'
     *
     * @param Request $request
     * @param boolean $absolute Generate absolute url or relative paths
     *
     * @return array
     * @throws ORMException
     */
    public function getTranslationMenuAssignation(Request $request, $absolute = false)
    {
        $attr = $request->attributes->all();
        $query = $request->query->all();
        $name = '';
        $forceLocale = (boolean) $this->settingsBag->get('force_locale');
        $useStaticRouting = !empty($attr['_route']) &&
            is_string($attr['_route']) &&
            $attr['_route'] !== RouteObjectInterface::OBJECT_BASED_ROUTE_NAME;

        /*
         * Fix absolute boolean to Int constant.
         */
        $absolute = $absolute ? Router::ABSOLUTE_URL : Router::ABSOLUTE_PATH;

        if (key_exists('node', $attr) && $attr['node'] instanceof Node) {
            $node = $attr["node"];
            $this->entityManager->refresh($node);
        } else {
            $node = null;
        }
        /*
         * If using a static route (routes.yml)…
         */
        if ($useStaticRouting) {
            $translations = $this->getRepository()->findAllAvailable();
            /*
             * Search for a route without Locale suffix
             */
            $baseRoute = RouteHandler::getBaseRoute($attr["_route"]);
            if (null !== $this->router->getRouteCollection()->get($baseRoute)) {
                $attr["_route"] = $baseRoute;
            }
        } elseif (null !== $node) {
            /*
             * If using dynamic routing…
             */
            if ($this->previewResolver->isPreview()) {
                $translations = $this->getRepository()->findAvailableTranslationsForNode($node);
            } else {
                $translations = $this->getRepository()->findStrictlyAvailableTranslationsForNode($node);
            }
            $name = "node";
        } else {
            return [];
        }

        $return = [];

        foreach ($translations as $translation) {
            $url = null;
            /*
             * Remove existing _locale in query string
             */
            if (key_exists('_locale', $query)) {
                unset($query["_locale"]);
            }
            /*
             * Remove existing page parameter in query string
             * if listing is different between 2 languages, maybe
             * page 2 or 3 does not exist in language B but exists in
             * language A
             */
            if (key_exists('page', $query)) {
                unset($query['page']);
            }

            if ($useStaticRouting) {
                $name = $attr['_route'];
                /*
                 * Use suffixed route if locales are forced or
                 * if it’s not default translation.
                 */
                if (true === $forceLocale || !$translation->isDefaultTranslation()) {
                    /*
                     * Search for a Locale suffixed route
                     */
                    if (null !== $this->router->getRouteCollection()->get($attr['_route'] . "Locale")) {
                        $name = $attr['_route'] . 'Locale';
                    }

                    $attr['_route_params']['_locale'] = $translation->getPreferredLocale();
                } else {
                    if (key_exists('_locale', $attr['_route_params'])) {
                        unset($attr['_route_params']['_locale']);
                    }
                }

                /*
                 * Remove existing page parameter in route parameters
                 * if listing is different between 2 languages, maybe
                 * page 2 or 3 does not exist in language B but exists in
                 * language A
                 */
                if (key_exists('page', $attr['_route_params'])) {
                    unset($attr['_route_params']['page']);
                }

                if (is_string($name)) {
                    $url = $this->router->generate(
                        $name,
                        array_merge($attr['_route_params'], $query),
                        $absolute
                    );
                } else {
                    $url = $this->router->generate(
                        RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                        array_merge($attr['_route_params'], $query, [
                            RouteObjectInterface::ROUTE_OBJECT => $name
                        ]),
                        $absolute
                    );
                }
            } elseif ($node) {
                $nodesSources = $node->getNodeSourcesByTranslation($translation)->first() ?: null;
                if (null !== $nodesSources && $nodesSources instanceof NodesSources) {
                    $url = $this->router->generate(
                        RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                        array_merge($query, [
                            RouteObjectInterface::ROUTE_OBJECT => $nodesSources
                        ]),
                        $absolute
                    );
                }
            }

            if (null !== $url) {
                $return[$translation->getPreferredLocale()] = [
                    'name' => $name,
                    'url' => $url,
                    'locale' => $translation->getPreferredLocale(),
                    'active' => $this->translation->getPreferredLocale() == $translation->getPreferredLocale(),
                    'translation' => $translation->getName(),
                ];
            }
        }
        return $return;
    }

    /**
     * @return Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param Translation $translation
     * @return TranslationViewer
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;
        return $this;
    }
}
