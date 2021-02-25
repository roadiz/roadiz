<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Utils\StringHandler;
use JMS\Serializer\Annotation as Serializer;

/**
 * UrlAliases are used to translate Nodes URLs.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\UrlAliasRepository")
 * @ORM\Table(name="url_aliases")
 */
class UrlAlias extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     * @Serializer\Groups({"url_alias"})
     */
    private $alias = '';

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
    /**
     * @param string $alias
     *
     * @return $this
     */
    public function setAlias(string $alias): UrlAlias
    {
        $this->alias = StringHandler::slugify($alias);
        return $this;
    }

    /**
     * @var NodesSources|null
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodesSources", inversedBy="urlAliases")
     * @ORM\JoinColumn(name="ns_id", referencedColumnName="id")
     * @Serializer\Exclude
     */
    private $nodeSource;

    /**
     * @return NodesSources
     */
    public function getNodeSource(): ?NodesSources
    {
        return $this->nodeSource;
    }
    /**
     * @param NodesSources|null $nodeSource
     * @return $this
     */
    public function setNodeSource(?NodesSources $nodeSource): UrlAlias
    {
        $this->nodeSource = $nodeSource;
        return $this;
    }
    /**
     * Create a new UrlAlias linked to a NodeSource.
     *
     * @param NodesSources|null $nodeSource
     */
    public function __construct(?NodesSources $nodeSource = null)
    {
        $this->setNodeSource($nodeSource);
    }
}
