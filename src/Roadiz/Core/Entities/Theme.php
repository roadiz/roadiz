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

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Themes describe a database entity to store
 * front-end and back-end controllers.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\ThemeRepository")
 * @ORM\Table(name="themes", indexes={
 *      @ORM\Index(columns={"backend"}),
 *      @ORM\Index(columns={"available"}),
 *      @ORM\Index(columns={"static_theme"})
 * })
 */
class Theme extends AbstractEntity
{
    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $available = false;
    /**
     * @return boolean
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @param boolean $available
     *
     * @return $this
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", name="static_theme", nullable=false)
     */
    protected $staticTheme = false;

    /**
     * Static means that your theme is not suitable for responding from
     * nodes urls but only static routes.
     *
     * @return boolean
     */
    public function isStaticTheme()
    {
        return (boolean) $this->staticTheme;
    }

    /**
     * @param boolean $newstaticTheme
     */
    public function setStaticTheme($newstaticTheme)
    {
        $this->staticTheme = (boolean) $newstaticTheme;

        return $this;
    }


    /**
     * @ORM\Column(name="classname", type="string", unique=true)
     * @var string
     */
    private $className;

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }
    /**
     * Get theme informations in an array.
     *
     * - name
     * - author
     * - copyright
     * - dir
     *
     * @return array
     */
    public function getInformations()
    {
        $class = $this->getClassName();

        return [
            'name'=> $class::getThemeName(),
            'author'=> $class::getThemeAuthor(),
            'copyright'=> $class::getThemeCopyright(),
            'dir'=> $class::getThemeDir()
        ];
    }

    /**
     * @ORM\Column(name="hostname",type="string")
     * @var string
     */
    private $hostname = '*';

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     *
     * @return $this
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * @ORM\Column(name="route_prefix",type="string", nullable=true)
     * @var string
     */
    private $routePrefix = '';

    /**
     * @return string
     */
    public function getRoutePrefix()
    {
        return $this->routePrefix;
    }

    /**
     * @param string $routePrefix
     *
     * @return $this
     */
    public function setRoutePrefix($routePrefix)
    {
        $this->routePrefix = $routePrefix;

        return $this;
    }

    /**
     * @ORM\Column(name="backend", type="boolean")
     * @var boolean
     */
    private $backendTheme = false;

    /**
     * @return boolean
     */
    public function isBackendTheme()
    {
        return $this->backendTheme;
    }

    /**
     * @param boolean $backendTheme
     *
     * @return $this
     */
    public function setBackendTheme($backendTheme)
    {
        $this->backendTheme = $backendTheme;

        return $this;
    }

    /**
    * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node")
    * @ORM\JoinColumn(name="homenode_id", referencedColumnName="id", onDelete="SET NULL")
    *
    * @var RZ\Roadiz\Core\Entities\Node
    */
    private $homeNode;

    /**
    * @param RZ\Roadiz\Core\Entities\Node $homeNode
    *
    * @return RZ\Roadiz\Core\Entities\Node
    */
    public function setHomeNode(Node $homeNode = null)
    {
        $this->homeNode = $homeNode;

        return $this;
    }

    /**
    * @return RZ\Roadiz\Core\Entities\Node
    */
    public function getHomeNode()
    {
        return $this->homeNode;
    }

    /**
    * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node")
    * @ORM\JoinColumn(name="root_id", referencedColumnName="id", onDelete="SET NULL")
    *
    * @var RZ\Roadiz\Core\Entities\Node
    */
    private $root;

    /**
    * @param RZ\Roadiz\Core\Entities\Node $root
    *
    * @return RZ\Roadiz\Core\Entities\Node
    */
    public function setRoot(Node $root = null)
    {
        $this->root = $root;

        return $this;
    }

    /**
    * @return RZ\Roadiz\Core\Entities\Node
    */
    public function getRoot()
    {
        return $this->root;
    }
}
