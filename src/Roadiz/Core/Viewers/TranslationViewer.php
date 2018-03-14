<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file TranslationViewer.php
 * @author Maxime Constantinian
 */

namespace RZ\Roadiz\Core\Viewers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Core\Routing\RouteHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

/**
 * TranslationViewer
 */
class TranslationViewer
{
    /** @var Settings */
    private $settingsBag;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var RouterInterface
     */
    private $router;
    private $translation;

    /**
     * TranslationViewer constructor.
     * @param EntityManager $entityManager
     * @param Settings $settingsBag
     * @param RouterInterface $router
     */
    public function __construct(EntityManager $entityManager, Settings $settingsBag, RouterInterface $router)
    {
        $this->settingsBag = $settingsBag;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    /**
     * @return TranslationRepository
     */
    public function getRepository()
    {
        return $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Translation');
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
     */
    public function getTranslationMenuAssignation(Request $request, $absolute = false)
    {
        $attr = $request->attributes->all();
        $query = $request->query->all();
        $name = "";
        $forceLocale = (boolean) $this->settingsBag->get('force_locale');

        /*
         * Fix absolute boolean to Int constant.
         */
        $absolute = $absolute ? Router::ABSOLUTE_URL : Router::ABSOLUTE_PATH;

        /** @var \Rz\Roadiz\Core\Entities\Node $node */
        if (in_array("node", array_keys($attr), true)) {
            $node = $attr["node"];
        } else {
            $node = null;
        }

        if ($node === null && !empty($attr["_route"])) {
            $translations = $this->getRepository()->findAllAvailable();
            $attr["_route"] = RouteHandler::getBaseRoute($attr["_route"]);
        } elseif (null !== $node) {
            $translations = $this->getRepository()->findAvailableTranslationsForNode($node);
            $translations = array_filter(
                $translations,
                function (Translation $trans) {
                    if ($trans->isAvailable()) {
                        return true;
                    }
                    return false;
                }
            );
            $name = "node";
        } else {
            return [];
        }

        $return = [];

        /** @var Translation $translation */
        foreach ($translations as $translation) {
            $url = null;

            if ($node) {
                $nodesSources = $this->entityManager
                    ->getRepository(NodesSources::class)
                    ->findOneBy(["node" => $node, "translation" => $translation]);
                $url = $this->router->generate(
                    $nodesSources,
                    $query,
                    $absolute
                );
            } elseif (!empty($attr["_route"])) {
                $name = $attr["_route"];
                /*
                 * Use suffixed route if locales are forced or
                 * if it’s not default translation.
                 */
                if (true === $forceLocale ||
                    !$translation->isDefaultTranslation()) {
                    /*
                     * Search for a Locale suffixed route
                     */
                    if (null !== $this->router->getRouteCollection()->get($attr["_route"] . "Locale")) {
                        $name = $attr["_route"] . "Locale";
                    }

                    $attr["_route_params"]["_locale"] = $translation->getLocale();
                } else {
                    if (in_array("_locale", array_keys($attr["_route_params"]), true)) {
                        unset($attr["_route_params"]["_locale"]);
                    }
                }
                /*
                 * Remove existing _locale in query string
                 */
                if (isset($query["_locale"])) {
                    unset($query["_locale"]);
                }

                $url = $this->router->generate(
                    $name,
                    array_merge($attr["_route_params"], $query),
                    $absolute
                );
            }

            if (null !== $url) {
                $return[$translation->getLocale()] = [
                    'name' => $name,
                    'url' => $url,
                    'locale' => $translation->getLocale(),
                    'active' => ($this->translation->getLocale() == $translation->getLocale()) ? true : false,
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
