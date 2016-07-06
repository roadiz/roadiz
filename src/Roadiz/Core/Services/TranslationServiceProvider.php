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

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Controllers\FrontendController;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Register Embed documents services for dependency injection container.
 */
class TranslationServiceProvider implements ServiceProviderInterface
{
    /**
     * Initialize translator services.
     *
     * @param \Pimple\Container $container
     *
     * @return \Pimple\Container
     */
    public function register(Container $container)
    {
        /**
         * @param $c
         * @return Translation
         */
        $container['defaultTranslation'] = function ($c) {
            return $c['em']->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findDefault();
        };

        /**
         * This service have to be called once a controller has
         * been matched! Never before.
         * @param $c
         * @return string
         */
        $container['translator.locale'] = function ($c) {
            if (null !== $c['request']->getLocale()) {
                return $c['request']->getLocale();
            } elseif (null !== $c['session']->get('_locale') &&
                $c['session']->get('_locale') != "") {
                return $c['session']->get('_locale');
            }

            return null;
        };

        /**
         * @param $c
         * @return Translator
         */
        $container['translator'] = function ($c) {
            $c['stopwatch']->start('initTranslator');
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];

            $translator = new Translator(
                $c['translator.locale'],
                null,
                $kernel->isDevMode() ? null : $kernel->getCacheDir() . '/translations',
                $kernel->isDebug()
            );

            $translator->addLoader('xlf', new XliffFileLoader());
            $classes = [$c['backendTheme']];
            $classes = array_merge($classes, $c['frontendThemes']);

            /*
             * DO NOT wake up entity manager in Install
             */
            if (!$kernel->isInstallMode()) {
                $availableTranslations = $c['em']->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                                 ->findAllAvailable();
                /** @var Translation $availableTranslation */
                foreach ($availableTranslations as $availableTranslation) {
                    $this->addResourcesForLocale($availableTranslation->getLocale(), $translator, $classes);
                }
            } else {
                $this->addResourcesForLocale($c['translator.locale'], $translator, $classes);
            }
            $c['stopwatch']->stop('initTranslator');

            return $translator;
        };

        return $container;
    }

    /**
     * @param string $locale
     * @param Translator $translator
     * @param array $classes
     */
    protected function addResourcesForLocale($locale, Translator $translator, array &$classes)
    {
        /*
         * Add general CMS translations
         */
        $this->addTranslatorResource(
            $translator,
            ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/translations',
            'xlf',
            $locale
        );

        /*
         * Add install theme translations
         */
        $this->addTranslatorResource(
            $translator,
            ROADIZ_ROOT . '/themes/Install/Resources/translations',
            'xlf',
            $locale
        );

        /** @var Theme $theme */
        foreach ($classes as $theme) {
            if (null !== $theme) {
                /** @var FrontendController $themeClass */
                $themeClass = $theme->getClassName();
                $this->addTranslatorResource(
                    $translator,
                    $themeClass::getResourcesFolder() . '/translations',
                    'xlf',
                    $locale
                );
            }
        }
    }

    /**
     * @param Translator $translator
     * @param string     $path
     * @param string     $extension
     * @param string     $locale
     */
    protected function addTranslatorResource(Translator $translator, $path, $extension, $locale)
    {
        $completePath = $path . '/messages.' . $locale . '.' . $extension;
        $fallbackPath = $path . '/messages.en.' . $extension;

        if (file_exists($completePath)) {
            $translator->addResource(
                $extension,
                $completePath,
                $locale
            );
        } elseif (file_exists($fallbackPath)) {
            $translator->addResource(
                $extension,
                $fallbackPath,
                $locale
            );
        }
    }
}
