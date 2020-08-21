<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Viewers\TranslationViewer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locales;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

/**
 * Extension that allow render document images
 */
class TranslationExtension extends AbstractExtension
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var TranslationViewer
     */
    private $translationViewer;

    /**
     * TranslationExtension constructor.
     * @param RequestStack $requestStack
     * @param TranslationViewer $translationViewer
     */
    public function __construct(RequestStack $requestStack, TranslationViewer $translationViewer)
    {
        $this->requestStack = $requestStack;
        $this->translationViewer = $translationViewer;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('menu', [$this, 'getMenuAssignation']),
            new TwigFilter('country_iso', [$this, 'getCountryName']),
            new TwigFilter('locale_iso', [$this, 'getLocaleName']),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('rtl', [$this, 'isLocaleRtl'])
        ];
    }

    /**
     * @param mixed $mixed
     *
     * @return bool
     */
    public function isLocaleRtl($mixed)
    {
        if ($mixed instanceof Translation) {
            return $mixed->isRtl();
        }

        if (is_string($mixed)) {
            return in_array($mixed, Translation::getRightToLeftLocales());
        }

        return false;
    }

    /**
     * @param Translation|null $translation
     * @param bool             $absolute
     *
     * @return array
     * @throws \Doctrine\ORM\ORMException
     */
    public function getMenuAssignation(Translation $translation = null, $absolute = false)
    {
        if (null !== $translation) {
            $this->translationViewer->setTranslation($translation);
            return $this->translationViewer->getTranslationMenuAssignation($this->requestStack->getCurrentRequest(), $absolute);
        } else {
            return [];
        }
    }

    /**
     * @param string $iso
     * @param string|null $locale
     * @return string
     */
    public function getCountryName(string $iso, ?string $locale = null): string
    {
        return Countries::getName($iso, $locale);
    }

    /**
     * @param string      $iso
     * @param string|null $locale
     *
     * @return string
     */
    public function getLocaleName(string $iso, ?string $locale = null): string
    {
        return Locales::getName($iso, $locale);
    }
}
