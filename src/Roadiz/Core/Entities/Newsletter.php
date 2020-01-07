<?php
declare(strict_types=1);
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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;

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
    private $status = Newsletter::DRAFT;

    /**
     * @return integer
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param integer $status
     * @return $this
     */
    public function setStatus(int $status): Newsletter
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDraft(): bool
    {
        return ($this->status === static::DRAFT);
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return ($this->status === static::PENDING);
    }

    /**
     * @return bool
     */
    public function isSending(): bool
    {
        return ($this->status === static::SENDING);
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return ($this->status === static::SENT);
    }

    /**
     * @ORM\OneToOne(targetEntity="RZ\Roadiz\Core\Entities\Node")
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id")
     */
    private $node;

    /**
     * @return Node
     */
    public function getNode(): ?Node
    {
        return $this->node;
    }

    /**
     * @param Node $node
     *
     * @return $this
     */
    public function setNode(Node $node): Newsletter
    {
        $this->node = $node;
        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\NewsletterSubscriber", mappedBy="newsletter")
     * @var ArrayCollection
     */
    private $newsletterSubscribers;

    /**
     * @return Collection
     */
    public function getNewsletterSubscribers(): Collection
    {
        return $this->newsletterSubscribers;
    }

    /**
     * @param Collection $newsletterSubscribers
     * @return Newsletter
     */
    public function setNewsletterSubscriber(Collection $newsletterSubscribers): Newsletter
    {
        $this->newsletterSubscribers = $newsletterSubscribers;
        return $this;
    }

    /**
     * Newsletter constructor.
     *
     * @param Node $node
     */
    public function __construct(Node $node)
    {
        $this->status = static::DRAFT;
        $this->node = $node;
        $this->newsletterSubscribers = new ArrayCollection();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->node = null;
            $this->newsletterSubscribers = new ArrayCollection();
        }
    }
}
