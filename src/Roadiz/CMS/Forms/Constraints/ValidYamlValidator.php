<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ValidYamlValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value != "") {
            try {
                if (is_array($value)) {
                    // value already has been parsed into array
                    return;
                }
                Yaml::parse($value);
            } catch (ParseException $e) {
                $this->context->addViolation($constraint->message, [
                    '{{ error }}' => $e->getMessage()
                ]);
            }
        }
    }
}
