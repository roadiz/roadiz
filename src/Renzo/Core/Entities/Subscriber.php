<?php
/*
 * Copyright REZO ZERO 2014
 *
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
