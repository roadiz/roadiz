<?php
/*
 * Copyright REZO ZERO 2014
 *

 * @file UrlAlias.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Utils\StringHandler;
/**
 * UrlAliases are used to translate Nodes URLs.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\UrlAliasRepository")
 * @Table(name="url_aliases")
 */
class UrlAlias extends AbstractEntity
{
    /**
     * @Column(type="string", unique=true)
     * @var string
     */
    private $alias;
    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
    /**
     * @param string $alias
     *
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = StringHandler::slugify($alias);

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\NodesSources", inversedBy="urlAliases")
     * @JoinColumn(name="ns_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $nodeSource;
    /**
     * @return RZ\Renzo\Core\Entities\NodesSources
     */
    public function getNodeSource()
    {
        return $this->nodeSource;
    }
    /**
     * @param RZ\Renzo\Core\Entities\NodesSources $nodeSource
     *
     * @return $this
     */
    public function setNodeSource($nodeSource)
    {
        $this->nodeSource = $nodeSource;

        return $this;
    }
    /**
     * Create a new UrlAlias linked to a NodeSource.
     *
     * @param NodesSources $nodeSource
     */
    public function __construct($nodeSource)
    {
        $this->setNodeSource($nodeSource);
    }
}