<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Model;

use Doctrine\Common\Collections\Collection;

interface AttributeGroupInterface
{
    public function getName(): ?string;
    public function setName(string $name);

    public function getCanonicalName(): ?string;

    public function getAttributes(): Collection;
    public function setAttributes(Collection $attributes);
}
