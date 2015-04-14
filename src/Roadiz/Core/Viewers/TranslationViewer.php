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
     * Return available page translation information
     *
     * ## example return value
     *
     *     array (size=3)
     *       'en' =>
     *         array (size=4)
     *             'name' => string 'newsPage' (length=8)
     *             'url' => string 'http://localhost/news/test' (length=26)
     *             'active' => boolean false
     *             'translation' => string 'English' (length=7)
     *       'fr' =>
     *         array (size=4)
     *             'name' => string 'newsPageLocale' (length=14)
     *             'url' => string 'http://localhost/fr/news/test' (length=29)
     *             'active' => boolean true
     *             'translation' => string 'French' (length=6)
     *       'es' =>
     *         array (size=4)
     *             'name' => string 'newsPageLocale' (length=14)
     *             'url' => string 'http://localhost/es/news/test' (length=29)
     *             'active' => boolean false
     *             'translation' => string 'Spanish' (length=2)
     *
     * @return $this
     */
    public function getTranslationMenuAssignation(Request $request)
    {
        $attr = $request->attributes->all();
        $query = $request->query->all();
        $name = "";

        if (in_array("node", array_keys($attr), true)) {
            $node = $attr["node"];
        } else {
            $node = null;
        }

        if ($node === null) {
            $translations = Kernel::getService('em')
                ->getRepository("RZ\Roadiz\Core\Entities\Translation")
                ->findAllAvailable();
            $attr["_route"] = RouteHandler::getBaseRoute($attr["_route"]);
        } else {
            $translations = $node->getHandler()->getAvailableTranslations();
            $translations = array_filter(
                $translations,
                function (Translation $x) {
                    if ($x->isAvailable()) {
                        return true;
                    }
                    return false;
                }
            );
            $name = "node";
        }

        $return = [];

        foreach ($translations as $translation) {
            if ($node) {
                $urlGenerator = new NodesSourcesUrlGenerator(
                    $request,
                    $node->getHandler()->getNodeSourceByTranslation($translation)
                );
                $url = $urlGenerator->getUrl();
                if (!empty($query)) {
                    $url .= "?" . http_build_query($query);
                }

            } else {
                if (!$translation->isDefaultTranslation()) {
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
                    array_merge($attr["_route_params"], $query)
                );
            }

            $return[$translation->getLocale()] = [
                'name' => $name,
                'url' => $url,
                'active' => ($this->translation == $translation) ? true : false,
                'translation' => $translation->getName(),
            ];
        }
        return $return;
    }

    /**
     * @return Symfony\Component\Translation\Translator.
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
