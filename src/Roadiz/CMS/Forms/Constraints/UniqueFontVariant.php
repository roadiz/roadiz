<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @package RZ\Roadiz\CMS\Forms\Constraints
 * @deprecated Use UniqueEntity constraint instead with "name" and "variant" fields
 */
class UniqueFontVariant extends Constraint
{
    public $entityManager = null;
    public $currentName = null;
    public $currentVariant = null;

    public $message = 'font.variant.alreadyExists';

    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}
