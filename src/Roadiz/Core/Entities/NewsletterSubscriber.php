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
 * @file NewsletterSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * Describes a simple ManyToMany relation
 * between Newsletter and Subscriber
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="newsletter_subscriber")
 */
class NewsletterSubscriber extends AbstractEntity
{
    const QUEUED = 10;
    const SENT = 20;
    const OPENED = 30;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Newsletter", inversedBy="newsletterSubscribers")
     * @ORM\JoinColumn(name="newsletter_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Newsletter
     */
    private $newsletter;

    /**
     * @return Newsletter
     */
    public function getNewsletter(): Newsletter
    {
        return $this->newsletter;
    }

    /**
     * @param Newsletter $ns
     *
     * @return NewsletterSubscriber
     */
    public function setNewsletter(Newsletter $ns): NewsletterSubscriber
    {
        $this->newsletter = $ns;
        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Subscriber", inversedBy="newsletterSubscriber")
     * @ORM\JoinColumn(name="subscriber_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @return Subscriber
     */
    public function getSubscriber(): Subscriber
    {
        return $this->subscriber;
    }

    /**
     * @param Subscriber $sub
     *
     * @return NewsletterSubscriber
     */
    public function setSubscriber(Subscriber $sub): NewsletterSubscriber
    {
        $this->subscriber = $sub;
        return $this;
    }

    /**
     * @ORM\Column(type="integer", unique=false)
     */
    private $status = NewsletterSubscriber::QUEUED;

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param integer $status
     * @return $this
     */
    public function setStatus(int $status): NewsletterSubscriber
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Create a new relation between Newsletter, a Subscriber.
     *
     * @param Newsletter $newsletter
     * @param Subscriber $subscriber
     */
    public function __construct(Newsletter $newsletter, Subscriber $subscriber)
    {
        $this->newsletter = $newsletter;
        $this->subscriber = $subscriber;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->newsletter = null;
        }
    }
}
