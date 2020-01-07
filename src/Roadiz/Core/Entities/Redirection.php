<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
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
 * @file Redirection.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use Symfony\Component\HttpFoundation\Response;

/**
 * Http redirection which are administrable by BO users.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="redirections")
 * @ORM\HasLifecycleCallbacks
 */
class Redirection extends AbstractDateTimed
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    private $query = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $redirectUri = "";

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodesSources")
     * @ORM\JoinColumn(name="ns_id", referencedColumnName="id", onDelete="CASCADE")
     * @var NodesSources
     */
    private $redirectNodeSource = null;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $type;

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param string $query
     * @return Redirection
     */
    public function setQuery($query): Redirection
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    /**
     * @param string|null $redirectUri
     * @return Redirection
     */
    public function setRedirectUri($redirectUri): Redirection
    {
        $this->redirectUri = $redirectUri;
        return $this;
    }

    /**
     * @return NodesSources|null
     */
    public function getRedirectNodeSource(): ?NodesSources
    {
        return $this->redirectNodeSource;
    }

    /**
     * @param NodesSources|null $redirectNodeSource
     * @return Redirection
     */
    public function setRedirectNodeSource(NodesSources $redirectNodeSource = null): Redirection
    {
        $this->redirectNodeSource = $redirectNodeSource;
        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTypeAsString(): string
    {
        $types = [
            Response::HTTP_MOVED_PERMANENTLY => 'redirection.moved_permanently',
            Response::HTTP_FOUND => 'redirection.moved_temporarily',
        ];

        return isset($types[$this->type]) ? $types[$this->type] : '';
    }

    /**
     * @param int $type
     * @return Redirection
     */
    public function setType(int $type): Redirection
    {
        $this->type = $type;
        return $this;
    }

    public function __construct()
    {
        $this->type = Response::HTTP_MOVED_PERMANENTLY;
    }
}
