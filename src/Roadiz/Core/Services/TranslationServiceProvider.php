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
use RZ\Roadiz\CMS\Controllers\CmsController;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Themes\Install\InstallApp;

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
            return $c['em']->getRepository(Translation::class)
                ->findDefault();
        };

        /**
         * This service have to be called once a controller has
         * been matched! Never before.
         * @param $c
         * @return string
         */
        $container['translator.locale'] = function ($c) {
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $request = $requestStack->getMasterRequest();

            if (null !== $request->getLocale()) {
                return $request->getLocale();
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
            /** @var ThemeResolverInterface $themeResolver */
            $themeResolver = $c['themeResolver'];

            $translator = new Translator(
                $c['translator.locale'],
                null,
                $kernel->isDevMode() ? null : $kernel->getCacheDir() . '/translations',
                $kernel->isDebug()
            );

            $translator->addLoader('xlf', new XliffFileLoader());
            $translator->addLoader('yml', new YamlFileLoader());
            $classes = array_merge(
                [$themeResolver->getBackendTheme()],
                $themeResolver->getFrontendThemes()
            );

            /*
             * DO NOT wake up entity manager in Install
             */
            if (!$kernel->isInstallMode()) {
                if ($kernel->isPreview()) {
                    $availableTranslations = $c['em']->getRepository(Translation::class)->findAll();
                } else {
                    $availableTranslations = $c['em']->getRepository(Translation::class)->findAllAvailable();
                }
                /** @var Translation $availableTranslation */
                foreach ($availableTranslations as $availableTranslation) {
                    $this->addResourcesForLocale($availableTranslation->getLocale(), $translator, $classes, $c['kernel']);
                }
            } else {
                $this->addResourcesForLocale($c['translator.locale'], $translator, $classes, $c['kernel']);
            }
            $c['stopwatch']->stop('initTranslator');

            return $translator;
        };

        return $container;
    }

    /**
     * @param string $locale
     * @param Translator $translator
     * @param Theme[] $classes
     * @param Kernel $kernel
     */
    protected function addResourcesForLocale($locale, Translator $translator, array &$classes, Kernel $kernel)
    {
        /*
         * Add existing Symfony validator translations
         */
        $vendorDir = $kernel->getVendorDir();
        $vendorFormDir = $vendorDir.'/symfony/form';
        $vendorValidatorDir = $vendorDir.'/symfony/validator';
        $validatorFromVendorFormDir = $vendorFormDir.'/Resources/translations/validators.'.$locale.'.xlf';
        $validatorFromVendorValidatorDir = $vendorValidatorDir.'/Resources/translations/validators.'.$locale.'.xlf';
        if (file_exists($validatorFromVendorFormDir)) {
            // there are built-in translations for the core error messages
            $translator->addResource(
                'xlf',
                $validatorFromVendorFormDir,
                $locale
            );
        }
        if (file_exists($validatorFromVendorValidatorDir)) {
            $translator->addResource(
                'xlf',
                $validatorFromVendorValidatorDir,
                $locale
            );
        }

        /*
         * Add general CMS translations
         */
        $this->addTranslatorResource(
            $translator,
            CmsController::getTranslationsFolder(),
            'xlf',
            $locale
        );
        $this->addTranslatorResource(
            $translator,
            CmsController::getTranslationsFolder(),
            'xlf',
            $locale,
            'validators'
        );

        /*
         * Add install theme translations
         */
        $this->addTranslatorResource(
            $translator,
            InstallApp::getTranslationsFolder(),
            'xlf',
            $locale
        );

        /** @var Theme $theme */
        foreach ($classes as $theme) {
            if (null !== $theme) {
                $resourcesFolder = call_user_func([$theme->getClassName(), 'getResourcesFolder']);
                $this->addTranslatorResource(
                    $translator,
                    $resourcesFolder . '/translations',
                    'xlf',
                    $locale
                );
                $this->addTranslatorResource(
                    $translator,
                    $resourcesFolder . '/translations',
                    'yml',
                    $locale
                );
            }
        }
    }

    /**
     * @param Translator $translator
     * @param string $path
     * @param string $extension
     * @param string $locale
     * @param null $domain
     */
    protected function addTranslatorResource(Translator $translator, $path, $extension, $locale, $domain = null)
    {
        $filename = 'messages';
        if ($domain !== null && $domain !== '') {
            $filename = $domain;
        }
        $completePath = $path . '/' . $filename . '.' . $locale . '.' . $extension;
        $fallbackPath = $path . '/' . $filename . '.en.' . $extension;

        if (file_exists($completePath)) {
            $translator->addResource(
                $extension,
                $completePath,
                $locale,
                $domain
            );
        } elseif (file_exists($fallbackPath)) {
            $translator->addResource(
                $extension,
                $fallbackPath,
                $locale,
                $domain
            );
        }
    }
}
