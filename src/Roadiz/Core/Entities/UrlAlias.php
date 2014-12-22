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
 * @file UrlAlias.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Utils\StringHandler;
use Doctrine\ORM\Mapping as ORM;

/**
 * UrlAliases are used to translate Nodes URLs.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\UrlAliasRepository")
 * @ORM\Table(name="url_aliases")
 */
class UrlAlias extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    private $alias;
    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
    /**
     * @param string $alias
     *
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = StringHandler::slugify($alias);

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodesSources", inversedBy="urlAliases", fetch="EAGER")
     * @ORM\JoinColumn(name="ns_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $nodeSource;
    /**
     * @return RZ\Roadiz\Core\Entities\NodesSources
     */
    public function getNodeSource()
    {
        return $this->nodeSource;
    }
    /**
     * @param RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     *
     * @return $this
     */
    public function setNodeSource($nodeSource)
    {
        $this->nodeSource = $nodeSource;

        return $this;
    }
    /**
     * Create a new UrlAlias linked to a NodeSource.
     *
     * @param NodesSources $nodeSource
     */
    public function __construct($nodeSource)
    {
        $this->setNodeSource($nodeSource);
    }
}
