<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Utils\StringHandler;

/**
 * Trait AttributeGroupTrait
 *
 * @package RZ\Roadiz\Attribute\Model
 */
trait AttributeGroupTrait
{
    /**
     * @var string|null
     */
    protected $name;
    /**
     * @var string|null
     */
    protected $canonicalName;
    /**
     * @var Collection
     */
    protected $attributes;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        $this->canonicalName = StringHandler::slugify($name);
        return $this;
    }

    public function getCanonicalName(): ?string
    {
        return $this->canonicalName;
    }

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function setAttributes(Collection $attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }
}
