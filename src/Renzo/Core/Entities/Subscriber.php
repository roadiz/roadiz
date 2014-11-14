<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file Subscriber.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractHuman;

/**
 * A Subscriber is a light User which only can subscribe
 * to newsletter feeds and can be tagged.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\EntityRepository")
 * @Table(name="subscribers")
 */
class Subscriber extends AbstractHuman
{
    /**
     * @Column(type="boolean")
     */
    private $hardBounced = false;
    /**
     * @return boolean
     */
    public function isHardBounced()
    {
        return $this->hardBounced;
    }
    /**
     * @param boolean $hardBounced
     *
     * @return $this
     */
    public function setHardBounced($hardBounced)
    {
        $this->hardBounced = (boolean) $hardBounced;

        return $this;
    }

    /**
     * @Column(type="boolean")
     */
    private $softBounced = false;
    /**
     * @return boolean
     */
    public function isSoftBounced()
    {
        return $this->softBounced;
    }
    /**
     * @param boolean $softBounced
     *
     * @return $this
     */
    public function setSoftBounced($softBounced)
    {
        $this->softBounced = (boolean) $softBounced;

        return $this;
    }

    /**
     * @ManyToMany(targetEntity="Tag", inversedBy="subscribers")
     * @JoinTable(name="subscribers_tags")
     * @var ArrayCollection
     */
    private $tags = null;
    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Create a new Subscriber
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
}
