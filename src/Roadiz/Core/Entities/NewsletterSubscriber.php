<?php
declare(strict_types=1);

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
