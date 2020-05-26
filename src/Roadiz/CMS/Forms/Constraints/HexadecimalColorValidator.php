<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class HexadecimalColorValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($constraint instanceof HexadecimalColor) {
            if (null !== $value && preg_match('#\#[0-9a-f]{6}#', strtolower($value)) === 0) {
                $this->context->addViolation($constraint->message);
            }
        }
    }
}
