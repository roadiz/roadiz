<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueNodeTypeName extends Constraint
{
    public $currentValue = null;
    public $message = 'nodeType.name.alreadyExists';
}
