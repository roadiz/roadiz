<?php
declare(strict_types=1);

namespace RZ\Roadiz\Translation\Twig;

use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locales;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class TranslationExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
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
