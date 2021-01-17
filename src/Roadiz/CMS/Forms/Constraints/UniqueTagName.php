<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueTagName extends Constraint
{
    public $currentValue = null;
    public $message = 'tagName.%name%.alreadyExists';
}
