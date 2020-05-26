<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SimpleLatinStringValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($constraint instanceof SimpleLatinString) {
            if (preg_match('#[^a-z_\s\-]#', strtolower($value)) === 1) {
                $this->context->addViolation($constraint->message);
            }
        }
    }
}
