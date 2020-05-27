<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NonSqlReservedWordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null !== $value) {
            $fieldName = StringHandler::variablize($value);
            $lowerName = strtolower($value);
            if (in_array($value, NonSqlReservedWord::$forbiddenNames) ||
                in_array($lowerName, NonSqlReservedWord::$forbiddenNames) ||
                in_array($fieldName, NonSqlReservedWord::$forbiddenNames)) {
                if ($constraint instanceof NonSqlReservedWord) {
                    $this->context->addViolation($constraint->message);
                }
            }
        }
    }
}
