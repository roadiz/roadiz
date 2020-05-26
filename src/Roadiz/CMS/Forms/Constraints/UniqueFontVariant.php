<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

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
