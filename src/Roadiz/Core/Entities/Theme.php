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
 * @file Theme.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * Themes describe a database entity to store
 * front-end and back-end controllers.
 */
class Theme extends AbstractEntity
{
    /**
     * @var boolean
     */
    private $available = false;

    /**
     * @return boolean
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * @param boolean $available
     *
     * @return $this
     */
    public function setAvailable(bool $available): Theme
    {
        $this->available = $available;
        return $this;
    }

    /**
     *
     */
    protected $staticTheme = false;

    /**
     * Static means that your theme is not suitable for responding from
     * nodes urls but only static routes.
     *
     * @return boolean
     */
    public function isStaticTheme(): bool
    {
        return (boolean) $this->staticTheme;
    }

    /**
     * @param boolean $staticTheme
     * @return $this
     */
    public function setStaticTheme(bool $staticTheme): Theme
    {
        $this->staticTheme = (boolean) $staticTheme;
        return $this;
    }


    /**
     * @var string
     */
    private $className;

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setClassName($className): Theme
    {
        $this->className = $className;
        return $this;
    }

    /**
     * Alias for getInformations.
     *
     * @return array
     */
    public function getInformation(): array
    {
        return $this->getInformations();
    }

    /**
     * Get theme information in an array.
     *
     * - name
     * - author
     * - copyright
     * - dir
     *
     * @return array
     */
    public function getInformations(): array
    {
        /** @var string|AppController $class */
        $class = $this->getClassName();

        if (class_exists($class)) {
            $reflector = new \ReflectionClass($class);
            if ($reflector->isSubclassOf(AppController::class)) {
                return [
                    'name'=> call_user_func([$class, 'getThemeName']),
                    'author'=> call_user_func([$class, 'getThemeAuthor']),
                    'copyright'=> call_user_func([$class, 'getThemeCopyright']),
                    'dir'=> call_user_func([$class, 'getThemeDir'])
                ];
            }
        }

        return [];
    }

    /**
     * @var string
     */
    private $hostname = '*';

    /**
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     *
     * @return $this
     */
    public function setHostname(string $hostname): Theme
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * @var string
     */
    private $routePrefix = '';

    /**
     * @return string
     */
    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    /**
     * @param string $routePrefix
     *
     * @return $this
     */
    public function setRoutePrefix(string $routePrefix): Theme
    {
        $this->routePrefix = $routePrefix;
        return $this;
    }

    /**
     * @var boolean
     */
    private $backendTheme = false;

    /**
     * @return boolean
     */
    public function isBackendTheme(): bool
    {
        return $this->backendTheme;
    }

    /**
     * @param boolean $backendTheme
     * @return $this
     */
    public function setBackendTheme(bool $backendTheme): Theme
    {
        $this->backendTheme = $backendTheme;
        return $this;
    }
}
