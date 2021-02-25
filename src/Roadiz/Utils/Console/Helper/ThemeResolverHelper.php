<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Console\Helper\Helper;

class ThemeResolverHelper extends Helper
{
    protected ThemeResolverInterface $themeResolver;

    /**
     * @param ThemeResolverInterface $themeResolver
     */
    public function __construct(ThemeResolverInterface $themeResolver)
    {
        $this->themeResolver = $themeResolver;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'themeResolver';
    }

    /**
     * @return ThemeResolverInterface
     */
    public function getThemeResolver()
    {
        return $this->themeResolver;
    }
}
