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
 * @file NewsletterSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Newsletter;
use RZ\Roadiz\Core\Entities\Subscriber;
use Doctrine\ORM\Mapping as ORM;

/**
 * Describes a simple ManyToMany relation
 * between Newsletter and Subscriber
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesSourcesDocumentsRepository")
 * @ORM\Table(name="newsletter_subscriber")
 */
class NewsletterSubscriber extends AbstractEntity
{
    const QUEUED = 10;
    const SENT = 20;
    const OPENED = 30;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Newsletter", inversedBy="newsletterSubscriber")
     * @ORM\JoinColumn(name="newsletter_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Roadiz\Core\Entities\Newsletter
     */
    private $newsletter;

    /**
     * @return RZ\Roadiz\Core\Entities\Newsletter
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    public function setNewsletter($ns)
    {
        $this->newsletter = $ns;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Subscriber", inversedBy="newsletterSubscriber")
     * @ORM\JoinColumn(name="subscriber_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Roadiz\Core\Entities\Subscriber
     */
    private $subscriber;

    /**
     * @return RZ\Roadiz\Core\Entities\Subscriber
     */
    public function getSubscriber()
    {
        return $this->subscriber;
    }

    public function setSubscriber($sub)
    {
        $this->subscriber = $sub;
    }

    /**
     * @ORM\Column(type="integer", unique=false)
     */
    private $status = NewsletterSubscriber::QUEUED;

    /**
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param integer $status
     *
     * @return integer
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Create a new relation between Newsletter, a Subscriber.
     *
     * @param RZ\Roadiz\Core\Entities\Newsletter $newsletter
     * @param RZ\Roadiz\Core\Entities\Subscriber $subscriber
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
