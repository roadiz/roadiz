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

/**
* TranslationViewer
*/
class TranslationViewer implements ViewableInterface
{
    public function getTranslationMenuAssignation($routeInfo = null)
    {
        $translations = Kernel::getService('em')
            ->getRepository("Rz\Roadiz\Core\Entities\Translation")
            ->getAllAvailable();
        $return = ["availableLanguages" => []];
        foreach ($translations as $translation) {
            if (!$translation->isDefault()) {
                $routeInfo["_route"] = $routeInfo["_route"] . "Locale"
                $routeInfo["params"] = $routeInfo["params"]["_locale"] = $translation->getLocale();
            }
            $return["availableLanguages"][$translation->getLocale()] = [
                'name' => $routeInfo["_route"],
                'url' => Kernel::getService("urlGenerator")->generate(
                    [
                        $routeInfo["_route"],
                        $routeInfo["params"]
                    ]
                )
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
