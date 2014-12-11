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
 * @file TranslationServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Pimple\Container;

/**
 * Register Embed documents services for dependency injection container.
 */
class TranslationServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Initialize translator services.
     *
     * @param Pimple\Container $container
     *
     * @return Pimple\Container
     */
    public function register(Container $container)
    {
        /**
         * This service have to be called once a controller has
         * been matched! Never before.
         */
        $container['translator.locale'] = function ($c) {

            if ($c['session']->get('_locale') != "") {
                return $c['session']->get('_locale');
            } else {
                return Kernel::getInstance()->getRequest()->getLocale();
            }
        };

        $container['translator'] = function ($c) {
            $c['stopwatch']->start('initTranslations');

            $translator = new Translator($c['translator.locale']);
            $translator->addLoader('xlf', new XliffFileLoader());

            $CMSMsgPath = ROADIZ_ROOT.'/src/Roadiz/CMS/Resources/translations/messages.'.$c['translator.locale'].'.xlf';
            if (file_exists($CMSMsgPath)) {
                $translator->addResource(
                    'xlf',
                    $CMSMsgPath,
                    $c['translator.locale']
                );
            }
            $installPath = ROADIZ_ROOT.'/themes/Install/Resources/translations/messages.'.$c['translator.locale'].'.xlf';
            if (file_exists($installPath)) {
                $translator->addResource(
                    'xlf',
                    $installPath,
                    $c['translator.locale']
                );
            }

            $classes = array($c['backendTheme']);
            $classes = array_merge($classes, $c['frontendThemes']);

            foreach ($classes as $theme) {
                if (null !== $theme) {
                    $themeClass = $theme->getClassName();

                    $msgPath = $themeClass::getResourcesFolder().'/translations/messages.'.$c['translator.locale'].'.xlf';
                    if (file_exists($msgPath)) {
                        $translator->addResource(
                            'xlf',
                            $msgPath,
                            $c['translator.locale']
                        );
                    }
                }
            }

            $c['stopwatch']->stop('initTranslations');

            return $translator;
        };


        return $container;
    }
}
