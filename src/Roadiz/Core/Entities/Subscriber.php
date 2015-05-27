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
 * @file Subscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\AbstractEntities\AbstractHuman;
use Doctrine\ORM\Mapping as ORM;

/**
 * A Subscriber is a light User which only can subscribe
 * to newsletter feeds and can be tagged.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="subscribers")
 */
class Subscriber extends AbstractHuman
{
    /**
     * @ORM\Column(type="boolean")
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
     * @ORM\Column(type="boolean")
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
     * Create a new Subscriber
     */
    public function __construct()
    {

    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\NewsletterSubscriber", mappedBy="subscriber")
     */
    protected $newsletterSubscriber;

    /**
     * @return NewsletterSubscriber
     */
    public function getNewsletterSubscriber()
    {
        return $this->newsletterSubscriber;
    }

    /**
     * @param NewsletterSubscriber $newsletterSubscriber
     * @return NewsletterSubscriber
     */
    public function setNewsletterSubscriber($newsletterSubscriber)
    {
        $this->newsletterSubscriber = $newsletterSubscriber;
        return $this->newsletterSubscriber;
    }
}
