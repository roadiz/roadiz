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
 * @file Newsletter.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use RZ\Roadiz\Core\Handlers\NewsletterHandler;

/**
 * Newsletters entities wrap a Node and are linked to
 * Subscribers in order to render a HTML Email and send it over
 * MailTransportAgent.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="newsletters")
 */
class Newsletter extends AbstractDateTimed
{
    const DRAFT = 10;
    const PENDING = 20;
    const SENDING = 30;
    const SENT = 40;

    /**
     * @ORM\Column(type="integer", unique=false)
     */
    private $status;

    /**
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param integer $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function isDraft()
    {
        return ($this->status == static::DRAFT ? true : false);
    }

    public function isPending()
    {
        return ($this->status == static::PENDING ? true : false);
    }

    public function isSending()
    {
        return ($this->status == static::SENDING ? true : false);
    }

    public function isSent()
    {
        return ($this->status == static::SENT ? true : false);
    }

    /**
     * @ORM\OneToOne(targetEntity="RZ\Roadiz\Core\Entities\Node")
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id")
     */
    private $node;

    /**
     * @return \RZ\Roadiz\Core\Entities\Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\Node $node
     *
     * @return $this
     */
    public function setNode($node)
    {
        $this->node = $node;
        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\NewsletterSubscriber", mappedBy="newsletter")
     */
    private $newsletterSubscriber;

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

    public function __construct($node)
    {
        $this->status = static::DRAFT;
        $this->node = $node;
    }

    private $handler;

    /**
     * @return NewsletterHandler
     * @deprecated Use newsletter.handler service.
     */
    public function getHandler()
    {
        if (null === $this->handler) {
            $this->handler = new NewsletterHandler($this);
        }
        return $this->handler;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->node = null;
            $this->newsletterSubscriber = null;
        }
    }
}
