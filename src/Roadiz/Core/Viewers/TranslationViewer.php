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

use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Routing\RouteHandler;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;
use Symfony\Component\HttpFoundation\Request;

/**
 * TranslationViewer
 */
class TranslationViewer implements ViewableInterface
{
    protected $translation;

    public function __construct(Translation $translation)
    {
        $this->translation = $translation;
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
     * @return $this
     */
    public function getTranslationMenuAssignation(Request $request, $absolute = false)
    {
        $attr = $request->attributes->all();
        $query = $request->query->all();
        $name = "";
        $forceLocale = (boolean) SettingsBag::get('force_locale');

        /** @var \Rz\Roadiz\Core\Entities\Node $node */
        if (in_array("node", array_keys($attr), true)) {
            $node = $attr["node"];
        } else {
            $node = null;
        }

        if ($node === null && !empty($attr["_route"])) {
            $translations = Kernel::getService('em')
                ->getRepository("RZ\Roadiz\Core\Entities\Translation")
                ->findAllAvailable();
            $attr["_route"] = RouteHandler::getBaseRoute($attr["_route"]);
        } elseif (null !== $node) {
            $translations = $node->getHandler()->getAvailableTranslations();
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

        foreach ($translations as $translation) {
            $url = null;

            if ($node) {
                $urlGenerator = new NodesSourcesUrlGenerator(
                    $request,
                    $node->getHandler()->getNodeSourceByTranslation($translation),
                    $forceLocale
                );
                $url = $urlGenerator->getUrl($absolute);
                if (!empty($query)) {
                    $url .= "?" . http_build_query($query);
                }

            } elseif (!empty($attr["_route"])) {
                /*
                 * Use suffixed route if locales are forced or
                 * if it’s not default translation.
                 */
                if (true === $forceLocale ||
                    !$translation->isDefaultTranslation()) {
                    $name = $attr["_route"] . "Locale";
                    $attr["_route_params"]["_locale"] = $translation->getLocale();
                } else {
                    $name = $attr["_route"];
                    if (in_array("_locale", array_keys($attr["_route_params"]), true)) {
                        unset($attr["_route_params"]["_locale"]);
                    }
                }
                $url = Kernel::getService("urlGenerator")->generate(
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
                    'active' => ($this->translation == $translation) ? true : false,
                    'translation' => $translation->getName(),
                ];
            }
        }
        return $return;
    }

    /**
     * @return \Symfony\Component\Translation\Translator.
     */
    public function getTranslator()
    {
        return null;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return Kernel::getService('twig.environment');
    }
}
