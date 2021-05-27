<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use Symfony\Component\HttpFoundation\Response;

/**
 * Http redirection which are editable by BO users.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="redirections")
 * @ORM\HasLifecycleCallbacks
 */
class Redirection extends AbstractDateTimed
{
    /**
     * @ORM\Column(type="string", unique=true, length=255)
     * @var string
     */
    private $query = "";

    /**
     * @ORM\Column(type="text", nullable=true, length=2048)
     * @var string|null
     */
    private $redirectUri = null;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodesSources")
     * @ORM\JoinColumn(name="ns_id", referencedColumnName="id", onDelete="CASCADE")
     * @var NodesSources|null
     */
    private $redirectNodeSource = null;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $type;

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param string $query
     * @return Redirection
     */
    public function setQuery($query): Redirection
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    /**
     * @param string|null $redirectUri
     * @return Redirection
     */
    public function setRedirectUri($redirectUri): Redirection
    {
        $this->redirectUri = $redirectUri;
        return $this;
    }

    /**
     * @return NodesSources|null
     */
    public function getRedirectNodeSource(): ?NodesSources
    {
        return $this->redirectNodeSource;
    }

    /**
     * @param NodesSources|null $redirectNodeSource
     * @return Redirection
     */
    public function setRedirectNodeSource(NodesSources $redirectNodeSource = null): Redirection
    {
        $this->redirectNodeSource = $redirectNodeSource;
        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTypeAsString(): string
    {
        $types = [
            Response::HTTP_MOVED_PERMANENTLY => 'redirection.moved_permanently',
            Response::HTTP_FOUND => 'redirection.moved_temporarily',
        ];

        return isset($types[$this->type]) ? $types[$this->type] : '';
    }

    /**
     * @param int $type
     * @return Redirection
     */
    public function setType(int $type): Redirection
    {
        $this->type = $type;
        return $this;
    }

    public function __construct()
    {
        $this->type = Response::HTTP_MOVED_PERMANENTLY;
        $this->initAbstractDateTimed();
    }
}
