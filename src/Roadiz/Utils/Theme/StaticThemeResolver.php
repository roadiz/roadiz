<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file StaticThemeResolver.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Theme;

use RZ\Roadiz\Core\Entities\Theme;
use Themes\Rozier\RozierApp;

class StaticThemeResolver implements ThemeResolverInterface
{
    /**
     * @var array
     */
    protected $themesConfig = [];

    /**
     * @var array
     */
    protected $frontendThemes = [];

    /**
     * StaticThemeResolver constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        if (isset($configuration['themes'])) {
            $this->themesConfig = $configuration['themes'];
            foreach ($this->themesConfig as $index => $themeConfig) {
                $this->frontendThemes[] = $this->getThemeFromConfig($themeConfig, $index);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getBackendTheme()
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
    public function getBackendClassName()
    {
        return RozierApp::class;
    }

    /**
     * @inheritDoc
     */
    public function findTheme($host)
    {
        $default = null;

        foreach ($this->getFrontendThemes() as $theme) {
            if ($theme->getHostname() === $host) {
                return $theme;
            } elseif ($theme->getHostname() === '*') {
                $default = $theme;
            }
        }
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function findThemeByClass($classname)
    {
        foreach ($this->getFrontendThemes() as $theme) {
            if ($theme->getClassName() === $classname) {
                return $theme;
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function findAll()
    {
        return array_merge([
            $this->getBackendTheme(),
        ], $this->getFrontendThemes());
    }

    /**
     * @inheritDoc
     */
    public function findById($id)
    {
        if (isset($this->getFrontendThemes()[$id])) {
            return $this->getFrontendThemes()[$id];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getFrontendThemes()
    {
        return $this->frontendThemes;
    }

    /**
     * @param array $themeConfig
     * @param int $id
     *
     * @return Theme
     */
    private function getThemeFromConfig(array $themeConfig, $id = 0)
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
}
