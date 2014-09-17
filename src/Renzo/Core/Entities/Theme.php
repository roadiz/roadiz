<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file Theme.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;

/**
 * Themes describe a database entity to store
 * front-end and back-end controllers.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="themes", indexes={
 *      @index(name="backend_theme_idx", columns={"backend"}),
 *      @index(name="available_theme_idx", columns={"available"})
 * })
 */
class Theme extends AbstractEntity
{
    /**
     * @Column(type="boolean")
     * @var boolean
     */
    private $available = false;
    /**
     * @return boolean
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @param boolean $available
     *
     * @return $this
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @Column(name="classname", type="string", unique=true)
     * @var string
     */
    private $className;

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }
    /**
     * Get theme informations in an array.
     *
     * - name
     * - author
     * - copyright
     * - dir
     *
     * @return array
     */
    public function getInformations()
    {
        $class = $this->getClassName();

        return array(
            'name'=> $class::getThemeName(),
            'author'=> $class::getThemeAuthor(),
            'copyright'=> $class::getThemeCopyright(),
            'dir'=> $class::getThemeDir()
        );
    }

    /**
     * @Column(name="hostname",type="string")
     * @var string
     */
    private $hostname = '*';

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     *
     * @return $this
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * @Column(name="backend", type="boolean")
     * @var boolean
     */
    private $backendTheme = false;

    /**
     * @return boolean
     */
    public function isBackendTheme()
    {
        return $this->backendTheme;
    }

    /**
     * @param boolean $backendTheme
     *
     * @return $this
     */
    public function setBackendTheme($backendTheme)
    {
        $this->backendTheme = $backendTheme;

        return $this;
    }
}
