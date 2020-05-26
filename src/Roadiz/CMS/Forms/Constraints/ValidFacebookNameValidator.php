<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Utils\MediaFinders\FacebookPictureFinder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidFacebookNameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value != "") {
            if (0 === preg_match("#^[0-9]*$#", $value)) {
                $this->context->addViolation($constraint->message);
            } else {
                /*
                 * Test if the user name really exists.
                 */
                $facebook = new FacebookPictureFinder($value);
                try {
                    $facebook->getPictureUrl();
                } catch (\Exception $e) {
                    $this->context->addViolation($constraint->message);
                }
            }
        }
    }
}
