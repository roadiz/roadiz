<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class HexadecimalColor extends Constraint
{
    public $message = 'color.should.be.formatted.in.hexadecimal';
}
