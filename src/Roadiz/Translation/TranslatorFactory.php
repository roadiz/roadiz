<?php
declare(strict_types=1);

namespace RZ\Roadiz\Translation;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Controllers\CmsController;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\KernelInterface;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Themes\Install\InstallApp;
use Themes\Rozier\RozierApp;

final class TranslatorFactory implements TranslatorFactoryInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var EntityManagerInterface|null
     */
    protected $entityManager;

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @var ThemeResolverInterface
     */
    protected $themeResolver;

    /**
     * @param KernelInterface $kernel
     * @param RequestStack $requestStack
     * @param EntityManagerInterface|null $entityManager
     * @param Stopwatch $stopwatch
     * @param ThemeResolverInterface $themeResolver
     */
    public function __construct(
        KernelInterface $kernel,
        RequestStack $requestStack,
        ?EntityManagerInterface $entityManager,
        Stopwatch $stopwatch,
        ThemeResolverInterface $themeResolver
    ) {
        $this->kernel = $kernel;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->stopwatch = $stopwatch;
        $this->themeResolver = $themeResolver;
    }

    /**
     * @return TranslatorInterface
     * @throws \ReflectionException
     */
    public function create(): TranslatorInterface
    {
        $this->stopwatch->start('createTranslator');

        $translator = new Translator(
            $this->getCurrentLocale(),
            null,
            $this->kernel->isDebug() ? null : $this->kernel->getCacheDir() . '/translations',
            $this->kernel->isDebug()
        );

        $translator->addLoader('xlf', new XliffFileLoader());
        $translator->addLoader('yml', new YamlFileLoader());
        $classes = array_merge(
            [$this->themeResolver->getBackendTheme()],
            $this->themeResolver->getFrontendThemes()
        );

        foreach ($this->getAvailableLocales() as $locale) {
            $this->addResourcesForLocale($locale, $translator, $classes);
        }

        $this->stopwatch->stop('createTranslator');

        return $translator;
    }

    /**
     * @param string $locale
     * @param Translator $translator
     * @param Theme[] $classes
     * @throws \ReflectionException
     */
    protected function addResourcesForLocale(string $locale, Translator $translator, array &$classes)
    {
        /*
         * Add existing Symfony validator translations
         */
        $vendorDir = $this->kernel->getVendorDir();
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

    /**
     * @return string|null
     */
    protected function getCurrentLocale(): ?string
    {
        $request = $this->requestStack->getMasterRequest();
        if (null === $request) {
            return null;
        }
        if ($request->hasPreviousSession() &&
            null !== $request->getSession() &&
            null !== $request->getSession()->get('_locale')) {
            return $request->getSession()->get('_locale');
        }
        return $request->getLocale();
    }

    /**
     * @return array<string>
     */
    protected function getAvailableLocales(): array
    {
        // Add Rozier backend languages
        $locales = array_values(RozierApp::$backendLanguages);
        // Add default translation
        $locales[] = $this->getCurrentLocale();

        if (null !== $this->entityManager) {
            try {
                if ($this->kernel->getEnvironment() !== 'install') {
                    /** @var TranslationRepository $translationRepository */
                    $translationRepository = $this->entityManager->getRepository(Translation::class);
                    if ($this->kernel->isPreview()) {
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
            } catch (\PDOException $e) {
                // Trying to use translator without DB
                // in CI or CLI environments
            }
        }

        return array_unique(array_filter($locales));
    }
}
