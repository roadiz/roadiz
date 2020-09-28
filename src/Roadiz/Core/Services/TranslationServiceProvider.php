<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\DBAL\Exception;
use PDOException;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Controllers\CmsController;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Themes\Install\InstallApp;
use Themes\Rozier\RozierApp;

/**
 * Register Embed documents services for dependency injection container.
 */
class TranslationServiceProvider implements ServiceProviderInterface
{
    /**
     * Initialize translator services.
     *
     * @param Container $container
     *
     * @return Container
     */
    public function register(Container $container)
    {
        /**
         * @param Container $c
         * @return Translation
         */
        $container['defaultTranslation'] = function (Container $c) {
            return $c['em']->getRepository(Translation::class)->findDefault();
        };

        /**
         * This service have to be called once a controller has
         * been matched! Never before.
         * @param Container $c
         * @return string
         */
        $container['translator.locale'] = function (Container $c) {
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            $request = $requestStack->getMasterRequest();

            if (null === $request) {
                return null;
            }
            if ($request->hasPreviousSession() &&
                null !== $request->getSession() &&
                null !== $request->getSession()->get('_locale')) {
                return $request->getSession()->get('_locale');
            }
            return $request->getLocale();
        };

        /**
         * @param Container $c
         * @return Translator
         */
        $container['translator'] = function (Container $c) {
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

            /** @var string $locale */
            foreach ($c['translator.locales'] as $locale) {
                $this->addResourcesForLocale($locale, $translator, $classes, $c['kernel']);
            }

            $c['stopwatch']->stop('initTranslator');

            return $translator;
        };

        /**
         * Get available Roadiz locales for translation.
         *
         * @param Container $c
         * @return string[]
         */
        $container['translator.locales'] = function (Container $c) {
            // Add Rozier backend languages
            $locales = array_values(RozierApp::$backendLanguages);
            // Add default translation
            $locales[] = $c['translator.locale'];

            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            try {
                if (!$kernel->isInstallMode()) {
                    /** @var TranslationRepository $translationRepository */
                    $translationRepository = $c['em']->getRepository(Translation::class);
                    if ($kernel->isPreview()) {
                        $availableTranslations = $translationRepository->findAll();
                    } else {
                        $availableTranslations = $translationRepository->findAllAvailable();
                    }
                    /** @var Translation $availableTranslation */
                    foreach ($availableTranslations as $availableTranslation) {
                        $locales[] = $availableTranslation->getLocale();
                    }
                }
            } catch (Exception $e) {
            } catch (PDOException $e) {
                // Trying to use translator without DB
                // in CI or CLI environments
            }

            return array_unique($locales);
        };

        return $container;
    }

    /**
     * @param string $locale
     * @param Translator $translator
     * @param Theme[] $classes
     * @param Kernel $kernel
     * @throws \ReflectionException
     */
    protected function addResourcesForLocale(string $locale, Translator $translator, array &$classes, Kernel $kernel)
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
            dirname(__FILE__) . '/../../Documentation/Resources/translations',
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
        /*
         * Add backoffice theme additional translations
         */
        $this->addTranslatorResource(
            $translator,
            RozierApp::getTranslationsFolder(),
            'xlf',
            $locale,
            null,
            'helps'
        );
        $this->addTranslatorResource(
            $translator,
            RozierApp::getTranslationsFolder(),
            'xlf',
            $locale,
            null,
            'settings'
        );

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
     * @param Translator  $translator
     * @param string      $path
     * @param string      $extension
     * @param string      $locale
     * @param string|null $domain
     * @param string      $filename
     */
    protected function addTranslatorResource(
        Translator $translator,
        string $path,
        string $extension,
        string $locale,
        string $domain = null,
        string $filename = 'messages'
    ) {
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
