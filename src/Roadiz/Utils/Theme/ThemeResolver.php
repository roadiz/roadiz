<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file ThemeResolver.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Theme;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Repositories\ThemeRepository;
use Symfony\Component\Stopwatch\Stopwatch;
use Themes\Rozier\RozierApp;

/**
 * ThemeResolver to get backend and frontend themes.
 */
class ThemeResolver implements ThemeResolverInterface
{
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var bool
     */
    protected $installMode;
    /**
     * @var Stopwatch
     */
    protected $stopwatch;
    /**
     * @var string|null
     */
    protected $backendClass = null;
    /**
     * @var Theme|null
     */
    protected $backendTheme = null;
    /**
     * @var Themes[]|null
     */
    protected $frontendThemes = null;

    /**
     * @param EntityManager $em
     * @param Stopwatch     $stopwatch
     * @param boolean       $installMode
     */
    public function __construct(EntityManager $em, Stopwatch $stopwatch, $installMode = false)
    {
        $this->em = $em;
        $this->installMode = $installMode;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @return ThemeRepository
     */
    protected function getRepository()
    {
        return $this->em->getRepository(Theme::class);
    }

    /**
     * @return Theme
     */
    public function getBackendTheme()
    {
        if (!$this->installMode) {
            if (null === $this->backendTheme) {
                $this->stopwatch->start('getBackendTheme');
                $this->backendTheme = $this->getRepository()->findAvailableBackend();
                $this->stopwatch->stop('getBackendTheme');
            }
            return $this->backendTheme;
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getBackendClassName()
    {
        if (null !== $this->getBackendTheme()) {
            if (null === $this->backendClass) {
                $this->backendClass = $this->getBackendTheme()->getClassName();
            }
            return $this->backendClass;
        }

        return RozierApp::class;
    }

    /**
     * @return Theme[]
     */
    public function getFrontendThemes()
    {
        if (!$this->installMode) {
            if (null === $this->frontendThemes) {
                $this->stopwatch->start('getFrontendThemes');
                $this->frontendThemes = $this->getRepository()->findAvailableFrontends();

                if (count($this->frontendThemes) === 0) {
                    return [];
                }
                $this->stopwatch->start('sortFrontendThemes');
                usort($this->frontendThemes, [static::class, 'compareThemePriority']);
                $this->stopwatch->stop('sortFrontendThemes');
                $this->stopwatch->stop('getFrontendThemes');
            }
            return $this->frontendThemes;
        } else {
            return [];
        }
    }


    /**
     * Get Theme front controller class FQN.
     *
     * @param string $host Current request host
     * @return null|Theme
     */
    public function findTheme($host)
    {
        if (!$this->installMode) {
            /*
             * First we look for theme according to hostname.
             */
            $theme = $this->getRepository()->findAvailableNonStaticFrontendWithHost($host);
            /*
             * If no theme for current host, we look for
             * any frontend available theme.
             */
            if (null === $theme) {
                $theme = $this->getRepository()->findFirstAvailableNonStaticFrontend();
            }
            return $theme;
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function findThemeByClass($classname)
    {
        return $this->getRepository()->findOneByClassName($classname);
    }

    /**
     * @inheritDoc
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * @inheritDoc
     */
    public function findById($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @param Theme $themeA
     * @param Theme $themeB
     *
     * @return int
     */
    public static function compareThemePriority(Theme $themeA, Theme $themeB)
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
