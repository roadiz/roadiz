<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractHuman;

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
     * @ORM\Column(type="string", name="unsubscribe_token", nullable=true)
     */
    protected $unsubscribeToken = '';

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    protected $hardBounced = false;
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
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    protected $softBounced = false;
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

    /**
     * Gets the value of unsubscribeToken.
     *
     * @return mixed
     */
    public function getUnsubscribeToken()
    {
        return $this->unsubscribeToken;
    }

    /**
     * Sets the value of unsubscribeToken.
     *
     * @param mixed $unsubscribeToken the unsubscribe token
     *
     * @return self
     */
    public function setUnsubscribeToken($unsubscribeToken)
    {
        $this->unsubscribeToken = $unsubscribeToken;

        return $this;
    }
}
