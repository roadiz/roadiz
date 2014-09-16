<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AbstractDateTimed.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
/**
 * An AbstractEntity with datetime fields to keep track of time with your items.
 *
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 */
abstract class AbstractDateTimed extends AbstractEntity
{
    /**
     * @Column(type="datetime", name="created_at")
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
    /**
     * @Column(type="datetime", name="updated_at")
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @PreUpdate
     */
    public function preUpdate()
    {
        $this->setUpdatedAt(new \DateTime("now"));
    }
    /**
     * @PrePersist
     */
    public function prePersist()
    {
        $this->setUpdatedAt(new \DateTime("now"));
        $this->setCreatedAt(new \DateTime("now"));
    }
    /**
     * Set creation and update date to *now*.
     *
     * @return $this
     */
    public function resetDates()
    {
        $this->setCreatedAt(new \DateTime("now"));
        $this->setUpdatedAt(new \DateTime("now"));

        return $this;
    }
}
