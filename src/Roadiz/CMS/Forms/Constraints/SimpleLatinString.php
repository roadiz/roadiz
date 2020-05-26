<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class SimpleLatinString extends Constraint
{
    public $message = 'string.should.only.contain.latin.characters';
}
