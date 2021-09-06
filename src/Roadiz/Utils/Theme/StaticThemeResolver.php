<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Theme;

use RZ\Roadiz\Core\Entities\Theme;
use Symfony\Component\Stopwatch\Stopwatch;

class StaticThemeResolver implements ThemeResolverInterface
{
    protected array $themesConfig = [];
    protected array $frontendThemes = [];
    protected Stopwatch $stopwatch;
    protected bool $installMode = false;

    /**
     * @param array     $configuration
     * @param Stopwatch $stopwatch
     * @param bool      $installMode
     */
    public function __construct(array $configuration, Stopwatch $stopwatch, bool $installMode = false)
    {
        $this->stopwatch = $stopwatch;
        $this->installMode = $installMode;

        if (isset($configuration['themes'])) {
            $this->stopwatch->start('parse_frontend_themes');
            $this->themesConfig = $configuration['themes'];
            foreach ($this->themesConfig as $index => $themeConfig) {
                $this->frontendThemes[] = $this->getThemeFromConfig($themeConfig, $index);
            }
            usort($this->frontendThemes, [static::class, 'compareThemePriority']);
            $this->stopwatch->stop('parse_frontend_themes');
        }
    }

    /**
     * @inheritDoc
     */
    public function getBackendTheme(): Theme
    {
        $theme = new Theme();
        $theme->setAvailable(true);
        $theme->setClassName($this->getBackendClassName());
        $theme->setBackendTheme(true);
        return $theme;
    }

    /**
     * @inheritDoc
     */
    public function getBackendClassName(): string
    {
        return '\\Themes\\Rozier\\RozierApp';
    }

    /**
     * @inheritDoc
     */
    public function findTheme(string $host = null): ?Theme
    {
        $default = null;
        /*
         * Search theme by beginning at the start of the array.
         * Getting high priority theme at last
         */
        $searchThemes = $this->getFrontendThemes();

        foreach ($searchThemes as $theme) {
            if ($theme->getHostname() === $host) {
                return $theme;
            } elseif ($theme->getHostname() === '*') {
                // Getting high priority theme at last option
                $default = $theme;
            }
        }
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function findThemeByClass(string $classname): ?Theme
    {
        foreach ($this->getFrontendThemes() as $theme) {
            if (ltrim($theme->getClassName(), '\\') === ltrim($classname, '\\')) {
                return $theme;
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        return array_merge([
            $this->getBackendTheme(),
        ], $this->getFrontendThemes());
    }

    /**
     * @inheritDoc
     */
    public function findById($id): ?Theme
    {
        if (isset($this->getFrontendThemes()[$id])) {
            return $this->getFrontendThemes()[$id];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getFrontendThemes(): array
    {
        return $this->frontendThemes;
    }

    /**
     * @param array $themeConfig
     * @param int $id
     * @return Theme
     */
    private function getThemeFromConfig(array $themeConfig, int $id = 0): Theme
    {
        $theme = new Theme();
        $theme->setId($id);
        $theme->setAvailable(true);
        $theme->setClassName($themeConfig['classname']);
        $theme->setBackendTheme(false);
        $theme->setStaticTheme(false);
        $theme->setHostname($themeConfig['hostname']);
        $theme->setRoutePrefix($themeConfig['routePrefix']);
        return $theme;
    }

    /**
     * @param Theme $themeA
     * @param Theme $themeB
     *
     * @return int
     */
    public static function compareThemePriority(Theme $themeA, Theme $themeB): int
    {
        $classA = $themeA->getClassName();
        $classB = $themeB->getClassName();

        if (call_user_func([$classA, 'getPriority']) === call_user_func([$classB, 'getPriority'])) {
            return 0;
        }
        if (call_user_func([$classA, 'getPriority']) > call_user_func([$classB, 'getPriority'])) {
            return 1;
        } else {
            return -1;
        }
    }
}
